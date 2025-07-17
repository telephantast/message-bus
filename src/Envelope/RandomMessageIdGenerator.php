<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class RandomMessageIdGenerator implements MessageIdGenerator
{
    /**
     * @param positive-int $bytes
     */
    public function __construct(
        private int $bytes = 16,
    ) {}

    public function generateMessageId(): string
    {
        return bin2hex(random_bytes($this->bytes));
    }
}
