<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class FindBy
{
    /**
     * @param non-empty-array<non-empty-string, CriterionResolver> $criteriaMapping
     */
    public function __construct(
        public readonly array $criteriaMapping,
    ) {}

    public function checkValidFor(\ReflectionClass $entityClass, \ReflectionClass $messageClass): void
    {
        foreach ($this->criteriaMapping as $propertyName => $criterionResolver) {
            $property = $entityClass->getProperty($propertyName);
            \assert(!$property->isStatic());

            if ($criterionResolver instanceof SelfValidatingCriterionResolver) {
                $criterionResolver->checkValidFor($messageClass);
            }
        }
    }

    /**
     * @return non-empty-array<non-empty-string, mixed>
     */
    public function resolve(Message $message): array
    {
        return array_map(
            static fn(CriterionResolver $resolver): mixed => $resolver->resolve($message),
            $this->criteriaMapping,
        );
    }
}
