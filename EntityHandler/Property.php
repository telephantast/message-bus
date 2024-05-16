<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
final readonly class Property implements CriterionResolver
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private string $name,
    ) {}

    public function checkValidFor(\ReflectionClass $messageClass): void
    {
        \assert($messageClass->hasProperty($this->name));
    }

    public function resolve(Message $message): mixed
    {
        return $message->{$this->name};
    }
}
