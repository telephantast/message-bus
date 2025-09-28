<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
final readonly class ConsumptionId
{
    /**
     * @param non-empty-string $consumer
     * @param non-empty-string $messageId
     */
    public function __construct(
        public string $consumer,
        public string $messageId,
    ) {}

    public function __toString(): string
    {
        return "{$this->consumer}/{$this->messageId}";
    }
}
