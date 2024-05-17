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
     * @param non-empty-string $queue
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        public string $queue,
        private HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private iterable $middlewares = [],
        private MessageBus $messageBus = new MessageBus(),
    ) {}

    public function handle(Envelope $envelope): void
    {
        $context = $this->messageBus->startContext($envelope);
        $context->setAttribute(new ConsumerQueue($this->queue));

        Pipeline::handle(
            messageContext: $context,
            handler: $this->handlerRegistry->get($envelope->getMessageClass()),
            middlewares: $this->middlewares,
        );
    }
}
