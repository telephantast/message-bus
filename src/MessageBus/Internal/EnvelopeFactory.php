<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Event;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\Metadata\IdGenerator;
use Thesis\MessageBus\Metadata\Kind;
use Thesis\MessageBus\OutgoingEnvelope;
use Thesis\MessageBus\Reply;

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

    public function build(Command|Event|Reply $message, ?Metadata $causeMetadata = null): Envelope
    {
        return new Envelope(
            payload: $message->payload,
            metadata: new Metadata(
                id: $id = $message->id ?? $this->idGenerator->generateId(),
                conversationId: $causeMetadata->conversationId ?? $id,
                causeId: $causeMetadata?->id,
                kind: match ($message::class) {
                    Command::class => Kind::Command,
                    Event::class => Kind::Event,
                    Reply::class => Kind::Reply,
                },
                origin: $this->origin,
                createdAt: $message->createdAt,
            ),
        );
    }

    public function buildOutgoing(Command|Event|Reply $message, ?Metadata $causeMetadata = null): OutgoingEnvelope
    {
        return new OutgoingEnvelope(
            route: $this->router->route($message, $causeMetadata),
            envelope: $this->build($message, $causeMetadata),
        );
    }
}
