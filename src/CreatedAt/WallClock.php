<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CreatedAt;

use Psr\Clock\ClockInterface;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus\CreatedAt
 */
final readonly class WallClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
