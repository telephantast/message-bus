<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;

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
     * @return (TResult is void ? null : TResult)
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed;
}
