<?php

declare(strict_types=1);

namespace Thesis\MessageBus\IdGenerator;

use Thesis\MessageBus\IdGenerator;

/**
 * @api
 *
 * For testing purposes only.
 */
final class Incremental implements IdGenerator
{
    public function __construct(
        private int $id = 1,
    ) {}

    public function generateId(): string
    {
        $id = $this->id;
        ++$this->id;

        return (string) $id;
    }
}
