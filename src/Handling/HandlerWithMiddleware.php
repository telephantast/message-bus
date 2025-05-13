<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 * @template TMessage of Message
 * @template TTransaction of object = object
 * @implements Handler<TMessage, TTransaction>
 */
final class HandlerWithMiddleware implements Handler
{
    /**
     * @param Handler<TMessage, TTransaction> $handler
     * @param iterable<Middleware> $middleware
     */
    public function __construct(
        private readonly Handler $handler,
        private readonly iterable $middleware,
    ) {}

    public string $id { get => $this->handler->id; }

    public function handle(Envelope $envelope, HandleContext $context): void
    {
        Pipeline::handle(
            handler: $this->handler,
            middleware: $this->middleware,
            envelope: $envelope,
            context: $context,
        );
    }
}
