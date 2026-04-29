<?php

declare(strict_types=1);

namespace Thesis\MessageBus\ConsumerRuntime;

use Thesis\MessageBus\ConsumerRuntime;
use Thesis\MessageBus\ConsumerRuntime\Internal\DispatchOutbox;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\Unrecoverable;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\Metadata\IdGenerator;
use Thesis\MessageBus\Metadata\Kind;
use Thesis\MessageBus\OutgoingEnvelope;
use Thesis\MessageBus\Route\Direct;
use Thesis\Transaction;

/**
 * @api
 *
 * @template-covariant Tx of object
 * @implements ConsumerRuntime<Tx>
 */
final readonly class OutboxRuntime implements ConsumerRuntime
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

    public function consume(string $endpoint, Envelope $envelope, callable $handler): void
    {
        $txHandle = ($this->beginTransaction)();
        $tx = $txHandle->inner;

        $id = new ConsumptionId($endpoint, $envelope->metadata->id);

        try {
            $outgoing = $handler($envelope, $tx);

            if ($outgoing !== []) {
                $this->outbox->store($tx, $id, new OutboxRecord($outgoing));

                $this->dispatcher->dispatch([
                    $this->buildDispatchOutboxEnvelope($endpoint, $envelope->metadata),
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

    /**
     * @param non-empty-string $endpoint
     */
    private function buildDispatchOutboxEnvelope(string $endpoint, Metadata $metadata): OutgoingEnvelope
    {
        return new OutgoingEnvelope(
            route: new Direct($endpoint),
            envelope: new Envelope(
                payload: DispatchOutbox::Payload,
                metadata: new Metadata(
                    id: $this->idGenerator->generateId(),
                    conversationId: $metadata->conversationId,
                    causeId: $metadata->id,
                    kind: Kind::Command,
                    origin: $endpoint,
                    createdAt: new \DateTimeImmutable(),
                ),
            ),
        );
    }

    public function startConsumer(string $endpoint, callable $handler): Consumer
    {
        return $this->receiver->startConsumer($endpoint, function (Envelope $envelope) use ($endpoint, $handler) {
            $txHandle = null;

            try {
                if ($envelope->payload === DispatchOutbox::Payload) {
                    return $this->handleDispatchOutbox($envelope->metadata);
                }

                $txHandle = ($this->beginTransaction)();
                $tx = $txHandle->inner;

                $id = new ConsumptionId($endpoint, $envelope->metadata->id);

                if ($this->inbox->isHandled($tx, $id)) {
                    $txHandle->rollback();
                    $txHandle = null;

                    $record = $this->outbox->find($id);

                    if ($record !== null && !$record->dispatched) {
                        $this->doDispatch($id, $record->envelopes);
                    }

                    return Disposition::Ack;
                }

                $outgoing = $handler($envelope, $tx);

                if ($outgoing !== []) {
                    $this->outbox->store($tx, $id, new OutboxRecord($outgoing));
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

    private function handleDispatchOutbox(Metadata $metadata): Disposition
    {
        $id = new ConsumptionId(
            endpoint: $metadata->origin,
            messageId: $metadata->causeId ?? throw new \LogicException('Invalid message'),
        );

        $record = $this->outbox->find($id);

        if ($record === null) {
            return Disposition::Retry;
        }

        if (!$record->dispatched) {
            $this->doDispatch($id, $record->envelopes);
        }

        return Disposition::Ack;
    }

    /**
     * @param non-empty-list<OutgoingEnvelope> $envelopes
     */
    private function doDispatch(ConsumptionId $id, array $envelopes): void
    {
        $this->dispatcher->dispatch($envelopes);
        $this->outbox->markDispatched($id);
    }
}
