<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

/**
 * @api
 */
final readonly class RecoverabilityPolicies implements RecoverabilityPolicy
{
    /**
     * @param list<RecoverabilityPolicy> $policies
     */
    public function __construct(
        private array $policies,
    ) {}

    public function onFailure(FailureContext $context): null|Retry|Action
    {
        foreach ($this->policies as $policy) {
            $decision = $policy->onFailure($context);

            if ($decision !== null) {
                return $decision;
            }
        }

        return null;
    }
}
