<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

/**
 * @api
 */
final readonly class FailureContext
{
    /**
     * @param non-empty-string $endpoint
     * @param non-negative-int $immediateRetryCount
     * @param non-negative-int $delayedRetryCount
     */
    public function __construct(
        public string $endpoint,
        public \Throwable $error,
        public \DateTimeImmutable $firstFailedAt,
        public int $immediateRetryCount,
        public int $delayedRetryCount,
    ) {}
}
