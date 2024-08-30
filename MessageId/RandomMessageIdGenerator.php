<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

/**
 * @api
 */
final class RandomMessageIdGenerator implements MessageIdGenerator
{
    /**
     * @param positive-int $bytes
     */
    public function __construct(
        private readonly int $bytes = 16,
    ) {}

    public function generate(): string
    {
        return bin2hex(random_bytes($this->bytes));
    }
}
