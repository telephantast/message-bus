<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final class Delay implements Stamp
{
    private const SECONDS_MULTIPLIER = 1000;
    private const MINUTES_MULTIPLIER = self::SECONDS_MULTIPLIER * 60;
    private const HOURS_MULTIPLIER = self::MINUTES_MULTIPLIER * 60;
    private const DAYS_MULTIPLIER = self::HOURS_MULTIPLIER * 24;

    public function __construct(
        public readonly int $milliseconds,
    ) {}

    public static function till(\DateTimeImmutable $time, \DateTimeImmutable $now = new \DateTimeImmutable()): self
    {
        return new self((int) $time->format('Uv') - (int) $now->format('Uv'));
    }

    public static function fromSeconds(int|float $seconds): self
    {
        return new self((int) round($seconds * self::SECONDS_MULTIPLIER));
    }

    public static function fromMinutes(int|float $minutes): self
    {
        return new self((int) round($minutes * self::MINUTES_MULTIPLIER));
    }

    public static function fromHours(int|float $hours): self
    {
        return new self((int) round($hours * self::HOURS_MULTIPLIER));
    }

    public static function fromDays(int|float $days): self
    {
        return new self((int) round($days * self::DAYS_MULTIPLIER));
    }
}
