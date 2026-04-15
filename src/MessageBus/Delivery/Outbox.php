<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Delivery;

use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Delivery;
use Thesis\MessageBus\Delivery\Outbox\Dispatch;
use Thesis\MessageBus\Delivery\Outbox\Store;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Draft;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\IdGenerator;
use Thesis\Transaction;

/**
 * @api
 *
 * @template-covariant Tx of object
 * @implements Delivery<Tx>
 */
final readonly class Outbox implements Delivery
{
    /**
     * @param \Closure(): Transaction<Tx> $beginTransaction
     * @param Inbox<Tx> $inbox
     * @param Store<Tx> $outbox
     */
    public function __construct(
        private Dispatcher $dispatcher,
        private Receiver $receiver,
        private \Closure $beginTransaction,
        private Inbox $inbox,
        private Store $outbox,
        private IdGenerator $idGenerator = new IdGenerator\Random(),
    ) {}

    public function consume(string $consumer, callable $handler, Envelope $envelope): void
    {
        $txHandle = ($this->beginTransaction)();
        $tx = $txHandle->inner;

        $id = new ConsumptionId($consumer, $envelope->metadata->id);

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                $this->outbox->store($id, $tx, $outgoing);

                $this->dispatcher->dispatch([
                    Draft::command(new Dispatch($id))->seal($consumer, $this->idGenerator, $envelope->metadata),
                ]);
            }

            $txHandle->commit();
        } catch (\Throwable $exception) {
            $txHandle->rollback();

            throw $exception;
        }

        if ($outgoing !== []) {
            try {
                $this->dispatcher->dispatch($outgoing);
                $this->outbox->markDispatched($id);
            } catch (\Throwable) {
            }
        }
    }

    public function startConsumer(string $consumer, callable $handler): callable
    {
        return $this->receiver->subscribe($consumer, function (Envelope $envelope) use ($consumer, $handler) {
            $txHandle = null;

            try {
                if ($envelope->payload instanceof Dispatch) {
                    return $this->handleDispatchOutbox($envelope->payload);
                }

                $txHandle = ($this->beginTransaction)();
                $tx = $txHandle->inner;

                $id = new ConsumptionId($consumer, $envelope->metadata->id);

                if ($this->inbox->isHandled($tx, $id)) {
                    $txHandle->commit();
                    $txHandle = null;

                    $record = $this->outbox->find($id);

                    if ($record !== null && !$record->dispatched) {
                        $this->dispatcher->dispatch($record->messages);
                        $this->outbox->markDispatched($id);
                    }

                    return Disposition::Ack;
                }

                $outgoing = $handler($envelope, $tx);

                if ($outgoing !== []) {
                    $this->outbox->store($id, $tx, $outgoing);
                }

                $txHandle->commit();
                $txHandle = null;

                if ($outgoing !== []) {
                    $this->dispatcher->dispatch($outgoing);
                    $this->outbox->markDispatched($id);
                }

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

    private function handleDispatchOutbox(Dispatch $command): Disposition
    {
        $record = $this->outbox->find($command->id);

        if ($record === null) {
            return Disposition::Retry;
        }

        if (!$record->dispatched) {
            $this->dispatcher->dispatch($record->messages);
            $this->outbox->markDispatched($command->id);
        }

        return Disposition::Ack;
    }
}
