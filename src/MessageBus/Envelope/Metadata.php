<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final class Metadata
{
    public bool $isCommand { get => $this->kind === Kind::Command; }

    public bool $isEvent { get => $this->kind === Kind::Event; }

    /**
     * @param class-string<T> $class
     * @param non-empty-string $source
     * @param non-empty-string $id
     * @param non-empty-string $conversationId
     * @param ?non-empty-string $causeId
     */
    public function __construct(
        public readonly string $class,
        public readonly Kind $kind,
        public readonly string $source,
        public readonly string $id,
        public readonly string $conversationId,
        public readonly ?string $causeId,
    ) {}
}
