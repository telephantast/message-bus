<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @template TTransaction of object
     * @param Envelope<TMessage> $envelope
     * @param Context<TTransaction> $context
     * @param Pipeline<TResult, TMessage, TTransaction> $pipeline
     * @return TResult
     */
    public function handle(Envelope $envelope, Context $context, Pipeline $pipeline): mixed;
}
