<?php

declare(strict_types=1);

namespace Thesis\MessageBus\IdGenerator;

use Thesis\MessageBus\IdGenerator;

/**
 * @api
 */
final readonly class Random implements IdGenerator
{
    /**
     * @param positive-int $bytes
     */
    public function __construct(
        private int $bytes = 16,
    ) {}

    public function generateId(): string
    {
        return bin2hex(random_bytes($this->bytes));
    }
}
