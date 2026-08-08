<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

/**
 * @api
 */
interface RecoverabilityPolicy
{
    /**
     * @return null|Action|Retry null when this policy does not apply to the failure
     */
    public function onFailure(FailureContext $context): null|Action|Retry;
}
