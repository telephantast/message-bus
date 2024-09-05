<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
final class Value implements CriterionResolver
{
    public function __construct(
        private readonly mixed $value,
    ) {}

    public function resolve(Message $message): mixed
    {
        return $this->value;
    }
}
