<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Metadata\Kind;

/**
 * @api
 */
final readonly class Metadata
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $conversationId
     * @param ?non-empty-string $causeId
     * @param non-empty-string $origin
     */
    public function __construct(
        public string $id,
        public string $conversationId,
        public ?string $causeId,
        public Kind $kind,
        public string $origin,
        public \DateTimeImmutable $createdAt,
    ) {}
}
