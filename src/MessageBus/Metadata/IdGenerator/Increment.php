<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\IdGenerator;

use Thesis\MessageBus\Metadata\IdGenerator;

/**
 * @api
 *
 * For testing purposes only.
 */
final class Increment implements IdGenerator
{
    public function __construct(
        private int $id = 1,
    ) {}

    public function generateId(): string
    {
        return (string) $this->id++;
    }
}
