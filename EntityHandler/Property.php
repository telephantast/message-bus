<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
final class Property implements SelfValidatingCriterionResolver
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
    ) {}

    public function checkValidFor(\ReflectionClass $messageClass): void
    {
        $property = $messageClass->getProperty($this->name);
        \assert($property->isPublic() && !$property->isStatic());
    }

    public function resolve(Message $message): mixed
    {
        return $message->{$this->name};
    }
}
