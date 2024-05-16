<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

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

    public function generate(): string
    {
        return bin2hex(random_bytes($this->bytes));
    }
}
