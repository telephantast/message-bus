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

    public function build(Command|Event|Reply $message, ?Metadata $causeMetadata = null): Envelope
    {
        return new Envelope(
            payload: $message->payload,
            metadata: new Metadata(
                id: $id = $message->id ?? $this->idGenerator->generateId(),
                conversationId: $causeMetadata->conversationId ?? $id,
                causeId: $causeMetadata?->id,
                replyCorrelationId: match ($message::class) {
                    Command::class => $message->replyCorrelationId,
                    Event::class => null,
                    Reply::class => $causeMetadata->replyCorrelationId
                        ?? throw new \LogicException('Cannot build a reply envelope: the cause message has no reply correlation id'),
                },
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
            delay: $message instanceof Command ? $message->delay : new TimeSpan(),
        );
    }
}
