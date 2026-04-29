<?php

declare(strict_types=1);

namespace Thesis\MessageBus\ConsumerRuntime;

/**
 * @api
 */
final readonly class ConsumptionId
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     */
    public function __construct(
        public string $endpoint,
        public string $messageId,
    ) {}

    public function __toString(): string
    {
        return "{$this->endpoint}/{$this->messageId}";
    }
}
