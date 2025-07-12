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
     * @param non-empty-string $endpoint
     * @param Envelope<TMessage> $envelope
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return TResult
     */
    public function handle(string $endpoint, Envelope $envelope, Context $context, Pipeline $pipeline): mixed;
}
