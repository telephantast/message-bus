<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Time;

/**
 * @api
 */
final readonly class TimeSpan
{
    private const int SECONDS_MULTIPLIER = 1000;
    private const int MINUTES_MULTIPLIER = self::SECONDS_MULTIPLIER * 60;
    private const int HOURS_MULTIPLIER = self::MINUTES_MULTIPLIER * 60;

    public static function timeDiff(\DateTimeImmutable $a, \DateTimeImmutable $b): self
    {
        return new self((int) $a->format('U') - (int) $b->format('U'));
    }

    public static function fromMilliseconds(int|float $milliseconds): self
    {
        return new self(self::from($milliseconds, 1));
    }

    public static function fromSeconds(int|float $seconds): self
    {
        return new self(self::from($seconds, self::SECONDS_MULTIPLIER));
    }

    public static function fromMinutes(int|float $minutes): self
    {
        return new self(self::from($minutes, self::MINUTES_MULTIPLIER));
    }

    public static function fromHours(int|float $hours): self
    {
        return new self(self::from($hours, self::HOURS_MULTIPLIER));
    }

    /**
     * @param positive-int $multiplier
     */
    private static function from(int|float $value, int $multiplier): int
    {
        if (\is_int($value)) {
            return $value * $multiplier;
        }

        return (int) round($value * $multiplier);
    }

    private function __construct(
        private int $milliseconds,
    ) {}

    public function toMilliseconds(): int
    {
        return $this->milliseconds;
    }
}
