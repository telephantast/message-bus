<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Route;

/**
 * @api
 */
final readonly class Fanout
{
    /**
     * @param class-string $eventClass
     */
    public function __construct(
        public string $eventClass,
    ) {}
}
