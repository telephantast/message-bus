<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Route;

use Thesis\Time\TimeSpan;

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
        public TimeSpan $delay = new TimeSpan(),
    ) {}
}
