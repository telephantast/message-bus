<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
interface CriterionResolver
{
    /**
     * @param \ReflectionClass<Message> $messageClass
     */
    public function checkValidFor(\ReflectionClass $messageClass): void;

    public function resolve(Message $message): mixed;
}
