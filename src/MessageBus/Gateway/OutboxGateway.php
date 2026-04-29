<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

use Thesis\MessageBus\Consumer;
use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Disposition;
use Thesis\MessageBus\Draft;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\Gateway;
use Thesis\MessageBus\Gateway\Outbox\Dispatch;
use Thesis\MessageBus\IdGenerator;
use Thesis\MessageBus\Receiver;
use Thesis\Transaction;

/**
 * @api
 *
 * @template-covariant Tx of object
 * @implements Gateway<Tx>
 */
final readonly class OutboxGateway implements Gateway
{
    /**
     * @param \Closure(): Transaction<Tx> $beginTransaction
     * @param Inbox<Tx> $inbox
     * @param Outbox<Tx> $outbox
     */
    public function __construct(
        private Dispatcher $dispatcher,
        private Receiver $receiver,
        private \Closure $beginTransaction,
        private Inbox $inbox,
        private Outbox $outbox,
        private IdGenerator $idGenerator = new IdGenerator\UuidV7(),
    ) {}

    public function consume(string $endpoint, callable $handler, Envelope $envelope): void
    {
        $txHandle = ($this->beginTransaction)();
        $tx = $txHandle->inner;

        $id = new ConsumptionId($endpoint, $envelope->metadata->id);

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                $this->outbox->store($id, $tx, $outgoing);

                $this->dispatcher->dispatch([
                    Draft::command(new Dispatch($id))->seal($endpoint, $this->idGenerator, $envelope->metadata),
                ]);
            }

            $txHandle->commit();
        } catch (\Throwable $exception) {
            $txHandle->rollback();

            throw $exception;
        }

        if ($outgoing !== []) {
            try {
                $this->doDispatch($id, $outgoing);
            } catch (\Throwable) {
            }
        }
    }

    public function startConsumer(string $endpoint, callable $handler): Consumer
    {
        return $this->receiver->startConsumer($endpoint, function (Envelope $envelope) use ($endpoint, $handler) {
            $txHandle = null;

            try {
                if ($envelope->payload instanceof Dispatch) {
                    return $this->handleDispatchOutbox($envelope->payload);
                }

                $txHandle = ($this->beginTransaction)();
                $tx = $txHandle->inner;

                $id = new ConsumptionId($endpoint, $envelope->metadata->id);

                if ($this->inbox->isHandled($tx, $id)) {
                    $txHandle->rollback();
                    $txHandle = null;

                    $record = $this->outbox->find($id);

                    if ($record !== null && !$record->dispatched) {
                        $this->doDispatch($id, $record->messages);
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
                    $this->doDispatch($id, $outgoing);
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
            $this->doDispatch($command->id, $record->messages);
        }

        return Disposition::Ack;
    }

    /**
     * @param non-empty-list<Envelope> $messages
     */
    private function doDispatch(ConsumptionId $id, array $messages): void
    {
        $this->dispatcher->dispatch($messages);
        $this->outbox->markDispatched($id);
    }
}
