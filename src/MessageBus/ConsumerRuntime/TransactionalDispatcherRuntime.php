<?php

declare(strict_types=1);

namespace Thesis\MessageBus\ConsumerRuntime;

use Thesis\MessageBus\ConsumerRuntime;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\TransactionalDispatcher;
use Thesis\Transaction;

/**
 * @api
 *
 * @template Tx of object
 * @implements ConsumerRuntime<Tx>
 */
final readonly class TransactionalDispatcherRuntime implements ConsumerRuntime
{
    /**
     * @param TransactionalDispatcher<Tx> $dispatcher
     * @param \Closure(): Transaction<Tx> $beginTransaction
     * @param Inbox<Tx> $inbox
     */
    public function __construct(
        private TransactionalDispatcher $dispatcher,
        private Receiver $receiver,
        private \Closure $beginTransaction,
        private Inbox $inbox,
    ) {}

    public function consume(string $endpoint, Envelope $envelope, callable $handler): void
    {
        $txHandle = ($this->beginTransaction)();
        $tx = $txHandle->inner;

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                $this->dispatcher->transactionalDispatch($tx, $outgoing);
            }

            $txHandle->commit();
        } catch (\Throwable $exception) {
            $txHandle->rollback();

            throw $exception;
        }
    }

    public function startConsumer(string $endpoint, callable $handler): Consumer
    {
        return $this->receiver->startConsumer($endpoint, function (Envelope $envelope) use ($endpoint, $handler) {
            $txHandle = null;

            try {
                $txHandle = ($this->beginTransaction)();
                $tx = $txHandle->inner;

                $id = new ConsumptionId($endpoint, $envelope->metadata->id);

                if ($this->inbox->isHandled($tx, $id)) {
                    $txHandle->rollback();
                    $txHandle = null;

                    return Disposition::Ack;
                }

                $outgoing = $handler($envelope, $tx);

                if ($outgoing !== []) {
                    $this->dispatcher->transactionalDispatch($tx, $outgoing);
                }

                $txHandle->commit();
                $txHandle = null;

                return Disposition::Ack;
            } catch (Unrecoverable) {
                $txHandle?->rollback();

                return Disposition::Reject;
            } catch (\Throwable) {
                $txHandle?->rollback();

                return Disposition::Retry;
            }
        });
    }
}
