<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

use Telephantast\Message\Message;

/**
 * @api
 */
interface CriterionResolver
{
    public function resolve(Message $message): mixed;
}
