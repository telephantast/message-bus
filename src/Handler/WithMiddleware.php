<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;

/**
 * @api
 * @template TMessage of Message
 * @implements Handler<TMessage>
 */
final readonly class WithMiddleware implements Handler
{
    /**
     * @param Handler<TMessage> $handler
     * @param iterable<Middleware> $middleware
     */
    public function __construct(
        private Handler $handler,
        private iterable $middleware,
    ) {}

    public function handle(Envelope $envelope, Context $context): Result
    {
        return Pipeline::handle(
            handler: $this->handler,
            middleware: $this->middleware,
            envelope: $envelope,
            context: $context,
        );
    }
}
