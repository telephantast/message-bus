<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Route;

/**
 * @api
 */
final readonly class Direct
{
    /**
     * @param non-empty-string $destination Endpoint name
     */
    public function __construct(
        public string $destination,
    ) {}
}
