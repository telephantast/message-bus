<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Recoverability;

use Thesis\MessageBus\Processing\Retry;
use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class LinearRetryPolicy implements RecoverabilityPolicy
{
    private TimeSpan $delayedRetryTimeIncrease;

    public function __construct(
        private int $immediateRetries = 1,
        private int $delayedRetries = 3,
        ?TimeSpan $delayedRetryInterval = null,
    ) {
        if ($this->immediateRetries < 0) {
            throw new \InvalidArgumentException('Immediate retry count must be non-negative.');
        }

        if ($this->delayedRetries < 0) {
            throw new \InvalidArgumentException('Delayed retry count must be non-negative.');
        }

        $this->delayedRetryTimeIncrease = $delayedRetryInterval ?? TimeSpan::fromSeconds(5);
    }

    public function onFailure(FailureContext $context): ?Retry
    {
        if ($context->immediateRetryCount < $this->immediateRetries) {
            return Retry::immediately();
        }

        if ($context->delayedRetryCount < $this->delayedRetries) {
            return new Retry(
                delay: $this->delayedRetryTimeIncrease->mul($context->delayedRetryCount + 1),
            );
        }

        return null;
    }
}
