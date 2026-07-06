<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\CommandDraft;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\EventDraft;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\Metadata\IdGenerator;
use Thesis\MessageBus\Metadata\Kind;
use Thesis\MessageBus\OutgoingEnvelope;
use Thesis\MessageBus\ReplyDraft;
use Thesis\Time\TimeSpan;

/**
 * @internal
 */
final readonly class EnvelopeFactory
{
    /**
     * @param non-empty-string $origin
     */
    public function __construct(
        private string $origin,
        private IdGenerator $idGenerator,
        private Router $router,
    ) {}

    public function build(CommandDraft|EventDraft|ReplyDraft $message, ?Metadata $causeMetadata = null): Envelope
    {
        return new Envelope(
            payload: $message->payload,
            metadata: new Metadata(
                id: $id = $message->id ?? $this->idGenerator->generateId(),
                conversationId: $causeMetadata->conversationId ?? $id,
                causeId: $causeMetadata?->id,
                kind: match ($message::class) {
                    CommandDraft::class => Kind::Command,
                    EventDraft::class => Kind::Event,
                    ReplyDraft::class => Kind::Reply,
                },
                origin: $this->origin,
                createdAt: $message->createdAt,
            ),
        );
    }

    public function buildOutgoing(CommandDraft|EventDraft|ReplyDraft $message, ?Metadata $causeMetadata = null): OutgoingEnvelope
    {
        return new OutgoingEnvelope(
            route: $this->router->route($message, $causeMetadata),
            envelope: $this->build($message, $causeMetadata),
            delay: $message instanceof CommandDraft ? $message->delay : new TimeSpan(),
        );
    }
}
