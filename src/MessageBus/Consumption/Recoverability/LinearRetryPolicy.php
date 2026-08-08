<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class LinearRetryPolicy implements RecoverabilityPolicy
{
    private TimeSpan $delayedRetryTimeIncrease;

    /**
     * @param non-negative-int $immediateRetries
     * @param non-negative-int $delayedRetries
     */
    public function __construct(
        private int $immediateRetries = 1,
        private int $delayedRetries = 3,
        ?TimeSpan $delayedRetryInterval = null,
    ) {
        $this->delayedRetryTimeIncrease = $delayedRetryInterval ?? TimeSpan::fromSeconds(5);
    }

    public function onFailure(FailureContext $context): null|Action|Retry
    {
        if ($context->immediateRetryCount < $this->immediateRetries) {
            return Action::RetryImmediately;
        }

        if ($context->delayedRetryCount < $this->delayedRetries) {
            return new Retry(
                delay: $this->delayedRetryTimeIncrease->mul($context->delayedRetryCount + 1),
            );
        }

        return null;
    }
}
