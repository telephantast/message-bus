<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
final readonly class SerializedMessage
{
    /**
     * @param ?non-empty-string $contentType
     * @param ?non-empty-string $contentEncoding
     */
    public function __construct(
        public string $payload,
        public ?string $contentType = null,
        public ?string $contentEncoding = null,
    ) {}
}
