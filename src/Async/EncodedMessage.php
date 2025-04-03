<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

/**
 * @api
 */
final readonly class EncodedMessage
{
    public function __construct(
        public string $type,
        public ?string $contentType,
        public string $body,
    ) {}
}
