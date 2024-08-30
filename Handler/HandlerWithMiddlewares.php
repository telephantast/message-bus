<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class HandlerWithMiddlewares implements Handler
{
    /**
     * @param Handler<TResult, TMessage> $handler
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private readonly Handler $handler,
        private readonly iterable $middlewares,
    ) {}

    public function id(): string
    {
        return $this->handler->id();
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return Pipeline::handle(
            messageContext: $messageContext,
            handler: $this->handler,
            middlewares: $this->middlewares,
        );
    }
}
