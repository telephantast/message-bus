<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

use Thesis\MessageBus\Consumer;
use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Disposition;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\Gateway;
use Thesis\MessageBus\Receiver;
use Thesis\MessageBus\TransactionalDispatcher;
use Thesis\Transaction;

/**
 * @api
 *
 * @template-covariant Tx of object
 * @implements Gateway<Tx>
 */
final readonly class TransactionalDispatcherGateway implements Gateway
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

    public function consume(string $endpoint, callable $handler, Envelope $envelope): void
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
