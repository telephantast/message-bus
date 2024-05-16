<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
final readonly class Value implements CriterionResolver
{
    public function __construct(
        private mixed $value,
    ) {}

    public function checkValidFor(\ReflectionClass $messageClass): void {}

    public function resolve(Message $message): mixed
    {
        return $this->value;
    }
}
