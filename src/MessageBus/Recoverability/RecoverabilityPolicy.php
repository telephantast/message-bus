<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Recoverability;

use Thesis\MessageBus\Processing\Retry;

/**
 * @api
 */
interface RecoverabilityPolicy
{
    /**
     * @return Retry|Action|null null when this policy does not apply to the failure
     */
    public function onFailure(FailureContext $context): null|Retry|Action;
}
