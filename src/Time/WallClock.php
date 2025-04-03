<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Time;

use Psr\Clock\ClockInterface;

/**
 * @api
 */
final readonly class WallClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
