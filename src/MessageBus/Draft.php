<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Envelope\Kind;
use Thesis\MessageBus\Envelope\Metadata;
use Thesis\MessageBus\IdGenerator\Random;
use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Draft
{
    /**
     * @template P of object
     * @param P $payload
     * @param ?non-empty-string $id
     * @param ?non-empty-string $conversationId
     * @return self<P>
     */
    public static function command(
        object $payload,
        TimeSpan $delay = new TimeSpan(),
        ?string $id = null,
        ?string $conversationId = null,
    ): self {
        return new self(
            kind: Kind::Command,
            payload: $payload,
            id: $id,
            conversationId: $conversationId,
            delay: $delay,
        );
    }

    /**
     * @template P of object
     * @param P $payload
     * @param ?non-empty-string $id
     * @param ?non-empty-string $conversationId
     * @return self<P>
     */
    public static function event(
        object $payload,
        ?string $id = null,
        ?string $conversationId = null,
    ): self {
        return new self(
            kind: Kind::Event,
            payload: $payload,
            id: $id,
            conversationId: $conversationId,
            delay: new TimeSpan(),
        );
    }

    /**
     * @param T $payload
     * @param ?non-empty-string $id
     * @param ?non-empty-string $conversationId
     */
    private function __construct(
        public Kind $kind,
        public object $payload,
        public ?string $id,
        public ?string $conversationId,
        public TimeSpan $delay,
    ) {}

    /**
     * @param non-empty-string $source
     * @param null|non-empty-string|Envelope $cause
     * @return Envelope<T>
     */
    public function seal(string $source, IdGenerator $idGenerator = new Random(), null|string|Envelope $cause = null): Envelope
    {
        return new Envelope(
            payload: $this->payload,
            metadata: new Metadata(
                class: $this->payload::class,
                kind: $this->kind,
                source: $source,
                id: $id = $this->id ?? $idGenerator->generateId(),
                conversationId: $this->conversationId ?? match (true) {
                    \is_string($cause) => $cause,
                    $cause instanceof Envelope => $cause->metadata->conversationId,
                    default => $id,
                },
                causeId: $cause instanceof Envelope ? $cause->metadata->id : $cause,
            ),
            delay: $this->delay,
        );
    }
}

/**
 * @api
 * @template P of object
 * @param P $payload
 * @param ?non-empty-string $id
 * @param ?non-empty-string $conversationId
 * @return Draft<P>
 */
function command(
    object $payload,
    TimeSpan $delay = new TimeSpan(),
    ?string $id = null,
    ?string $conversationId = null,
): Draft {
    return Draft::command(
        payload: $payload,
        delay: $delay,
        id: $id,
        conversationId: $conversationId,
    );
}

/**
 * @api
 * @template P of object
 * @param P $payload
 * @param ?non-empty-string $id
 * @param ?non-empty-string $conversationId
 * @return Draft<P>
 */
function event(
    object $payload,
    ?string $id = null,
    ?string $conversationId = null,
): Draft {
    return Draft::event(
        payload: $payload,
        id: $id,
        conversationId: $conversationId,
    );
}
