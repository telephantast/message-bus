<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @api
 */
interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Context<TResult, TMessage> $context
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return TResult
     */
    public function handle(Context $context, Pipeline $pipeline): mixed;
}
