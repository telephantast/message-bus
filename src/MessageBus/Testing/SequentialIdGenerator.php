<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Testing;

use Thesis\MessageBus\Identification\IdGenerator;

/**
 * @api
 */
final class SequentialIdGenerator implements IdGenerator
{
    public function __construct(
        private int $id = 1,
    ) {}

    public function generateId(): string
    {
        return (string) $this->id++;
    }
}
