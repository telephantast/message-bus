<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\HandlerRegistry;
use Telephantast\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Telephantast\MessageBus\MessageBus;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;

/**
 * @api
 */
final class Consumer
{
    /**
     * @param non-empty-string $queue
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        public readonly string $queue,
        private readonly HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private readonly iterable $middlewares = [],
        private readonly MessageBus $messageBus = new MessageBus(),
    ) {}

    public function handle(Envelope $envelope): void
    {
        $context = $this->messageBus->startContext($envelope);
        $context->setAttribute(new Queue($this->queue));

        Pipeline::handle(
            messageContext: $context,
            handler: $this->handlerRegistry->get($envelope->getMessageClass()),
            middlewares: $this->middlewares,
        );
    }
}
