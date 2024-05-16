<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\HandlerRegistry;
use Telephantast\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Telephantast\MessageBus\MessageBus;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class Consumer
{
    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private MessageBus $messageBus,
        private HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private iterable $middlewares = [],
    ) {}

    public function consume(Envelope $envelope): void
    {
        Pipeline::handle(
            messageContext: $this->messageBus->startContext($envelope),
            handler: $this->handlerRegistry->get($envelope->getMessageClass()),
            middlewares: $this->middlewares,
        );
    }
}
