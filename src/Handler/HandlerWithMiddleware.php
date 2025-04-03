<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final readonly class HandlerWithMiddleware implements Handler
{
    /**
     * @param Handler<TResult, TMessage> $handler
     * @param iterable<Middleware> $middleware
     */
    public function __construct(
        private Handler $handler,
        private iterable $middleware,
    ) {}

    public function id(): string
    {
        return $this->handler->id();
    }

    public function handle(Context $context): mixed
    {
        return Pipeline::handle(
            handler: $this->handler,
            middleware: $this->middleware,
            context: $context,
        );
    }
}
