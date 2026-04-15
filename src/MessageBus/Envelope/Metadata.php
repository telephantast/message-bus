<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Metadata
{
    /**
     * @param class-string<T> $class
     * @param non-empty-string $source
     * @param non-empty-string $id
     * @param non-empty-string $conversationId
     * @param ?non-empty-string $causeId
     */
    public function __construct(
        public string $class,
        public Kind $kind,
        public string $source,
        public string $id,
        public string $conversationId,
        public ?string $causeId,
    ) {}
}
