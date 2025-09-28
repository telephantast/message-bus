<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Amp\Cancellation;
use Amp\CancelledException;
use Psr\Log\LoggerInterface;
use Thesis\MessageBus\Ack;
use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\ErrorHandler;
use Thesis\MessageBus\Inbox\AcquireResult;
use Thesis\MessageBus\Inbox\Lock;
use Thesis\MessageBus\Reject;
use Thesis\MessageBus\ReliableDispatcher;
use Thesis\MessageBus\Retry;
use Thesis\Transaction;
use const Thesis\MessageBus\Ack;
use const Thesis\MessageBus\Retry;

/**
 * @internal
 *
 * @template-covariant Tx of object
 */
final readonly class InboxedHandler
{
    /**
     * @param non-empty-string $consumer
     * @param \Closure(Envelope, Tx): list<Envelope> $handler
     * @param \Closure(): Transaction<Tx> $beginTransaction
     * @param Lock<Tx> $inbox
     * @param ReliableDispatcher<Tx> $dispatcher
     */
    public function __construct(
        private string $consumer,
        private \Closure $handler,
        private ErrorHandler $errorHandler,
        private Cancellation $cancellation,
        private \Closure $beginTransaction,
        private Lock $inbox,
        private ReliableDispatcher $dispatcher,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Envelope $incoming): Ack|Retry|Reject
    {
        $id = new ConsumptionId($this->consumer, $incoming->metadata->id);

        try {
            $transaction = ($this->beginTransaction)();
        } catch (\Throwable $exception) {
            if ($exception instanceof CancelledException) {
                throw $exception;
            }

            $this->logger->notice('Failed to begin transaction, will retry', [
                'exception' => $exception,
                'consumer' => $id->consumer,
                'message_id' => $id->messageId,
            ]);

            return Retry;
        }

        $rollback = function () use ($id, $transaction): void {
            try {
                $transaction->rollback();
            } catch (\Throwable $exception) {
                $this->logger->notice('Failed to rollback transaction', [
                    'exception' => $exception,
                    'consumer' => $id->consumer,
                    'message_id' => $id->messageId,
                ]);
            }
        };

        try {
            $acquire = $this->inbox->acquire($transaction->inner, $id, $this->cancellation);
        } catch (\Throwable $exception) {
            $rollback();

            if ($exception instanceof CancelledException) {
                throw $exception;
            }

            $this->logger->notice('Failed to acquire inbox lock, will retry', [
                'exception' => $exception,
                'consumer' => $id->consumer,
                'message_id' => $id->messageId,
            ]);

            return Retry;
        }

        if ($acquire === AcquireResult::Locked) {
            $rollback();

            $this->logger->debug('Message is locked by another process, will retry', [
                'consumer' => $id->consumer,
                'message_id' => $id->messageId,
            ]);

            return Retry;
        }

        if ($acquire === AcquireResult::Consumed) {
            $rollback();

            $this->logger->debug('Message already consumed, re-dispatching outgoing messages', [
                'consumer' => $id->consumer,
                'message_id' => $id->messageId,
            ]);

            return $this->tryDispatch($id);
        }

        try {
            $outgoing = ($this->handler)($incoming, $transaction->inner);

            if ($outgoing !== []) {
                $this->dispatcher->record($id, $outgoing, $transaction->inner, $this->cancellation);
            }

            $transaction->commit();
        } catch (\Throwable $error) {
            $rollback();

            if ($error instanceof CancelledException) {
                throw $error;
            }

            return $this->errorHandler->handle($error, $incoming);
        }

        if ($outgoing !== []) {
            return $this->tryDispatch($id);
        }

        return Ack::Value;
    }

    private function tryDispatch(ConsumptionId $id): Ack|Retry
    {
        try {
            $this->dispatcher->dispatchRecorded($id, $this->cancellation);

            return Ack;
        } catch (CancelledException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to dispatch outgoing messages, will retry', [
                'exception' => $exception,
                'consumer' => $id->consumer,
                'message_id' => $id->messageId,
            ]);

            return Retry;
        }
    }
}
