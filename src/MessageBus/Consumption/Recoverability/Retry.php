<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class Retry
{
    public function __construct(
        public TimeSpan $delay,
    ) {}
}
