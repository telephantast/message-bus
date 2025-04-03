<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Async\TransportOptions;

/**
 * @api
 */
final readonly class PublishOptions
{
    /**
     * @param ?non-empty-string $messageId
     * @param array<string, mixed> $headers
     */
    public function __construct(
        public ?string $messageId = null,
        public array $headers = [],
        public ?TransportOptions $transportOptions = null,
    ) {}
}
