<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

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

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Envelope<TResult, TMessage> $envelope
     */
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
