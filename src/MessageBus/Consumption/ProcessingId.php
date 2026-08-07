<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

/**
 * @api
 */
final readonly class ProcessingId
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function __construct(
        public string $endpoint,
        public string $messageId,
    ) {}
}
