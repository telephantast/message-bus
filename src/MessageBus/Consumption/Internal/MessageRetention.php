<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;
use Thesis\Time\TimeSpan;

/**
 * @internal
 */
final readonly class MessageRetention
{
    /**
     * @param non-empty-string $endpoint
     * @param \Closure(\DateTimeImmutable): int $purger
     */
    public function __construct(
        private string $endpoint,
        private \Closure $purger,
        private LoggerInterface $logger,
        private ClockInterface $clock,
    ) {}

    public function purge(?TimeSpan $retentionPeriod = null): int
    {
        return ($this->purger)($this->cutoff($retentionPeriod));
    }

    /**
     * Starts periodic retention cleanup.
     *
     * @return \Closure(): void a stop callback
     */
    public function startPurger(?TimeSpan $retentionPeriod = null, ?TimeSpan $purgeInterval = null): \Closure
    {
        $purgeInterval ??= TimeSpan::fromHours(1);

        if (!$purgeInterval->isPositive()) {
            throw new \InvalidArgumentException('Retention purge interval must be positive.');
        }

        $watcherId = EventLoop::repeat(
            interval: $purgeInterval->toSeconds(6),
            closure: function () use ($retentionPeriod): void {
                try {
                    $before = $this->cutoff($retentionPeriod);
                    $deleted = ($this->purger)($before);

                    if ($deleted > 0) {
                        $this->logger->debug('Retained message records purged.', [
                            'endpoint' => $this->endpoint,
                            'deleted' => $deleted,
                            'before' => $before,
                        ]);
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error('Retained message records purge failed.', [
                        'exception' => $exception,
                        'endpoint' => $this->endpoint,
                    ]);
                }
            },
        );

        EventLoop::unreference($watcherId);

        return static fn() => EventLoop::cancel($watcherId);
    }

    private function cutoff(?TimeSpan $retentionPeriod): \DateTimeImmutable
    {
        $retentionPeriod ??= TimeSpan::fromDays(7);

        if (!$retentionPeriod->isPositive()) {
            throw new \InvalidArgumentException('Retention period must be positive.');
        }

        return $this->clock->now()->modify("-{$retentionPeriod->toMicroseconds()} microseconds");
    }
}
