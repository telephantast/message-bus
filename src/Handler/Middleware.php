<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Result;

interface Middleware
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Envelope<TMessage> $envelope
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return Result<TResult>
     */
    public function handle(Envelope $envelope, Context $context, Pipeline $pipeline): Result;
}
