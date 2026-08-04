<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Identification;

/**
 * @api
 *
 * For testing purposes only.
 */
final class IncrementGenerator implements IdGenerator
{
    public function __construct(
        private int $id = 1,
    ) {}

    public function generateId(): string
    {
        return (string) $this->id++;
    }
}
