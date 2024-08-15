<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final readonly class Exchange implements Stamp
{
    public function __construct(
        public string $exchange,
    ) {}
}
