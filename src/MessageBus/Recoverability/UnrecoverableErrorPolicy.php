<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Recoverability;

/**
 * @api
 */
final readonly class UnrecoverableErrorPolicy implements RecoverabilityPolicy
{
    /**
     * @param list<class-string<\Throwable>> $errorClasses
     */
    public function __construct(
        private array $errorClasses,
    ) {}

    public function onFailure(FailureContext $context): ?Action
    {
        if (array_any(
            $this->errorClasses,
            static fn($exceptionClass) => $context->error instanceof $exceptionClass,
        )) {
            return Action::Bury;
        }

        return null;
    }
}
