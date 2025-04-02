<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class Exchange implements Stamp
{
    public function __construct(
        public string $exchange,
    ) {}
}
