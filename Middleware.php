<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler\Pipeline;

/**
 * @api
 */
interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return TResult
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed;
}
