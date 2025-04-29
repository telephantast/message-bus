<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\ContextAttributes;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Thesis\MessageBus\MessageBus;

/**
 * @api
 */
final readonly class Consumer
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public string $queue,
        private HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private MessageBus $messageBus = new MessageBus(),
    ) {}

    public function consume(Envelope $envelope): void
    {
        $context = new Context(
            messageBus: $this->messageBus,
            envelope: $envelope,
            attributes: new ContextAttributes([new InConsumer($this->queue)]),
        );

        $this->handlerRegistry->get($envelope->message::class)->handle($context);
    }
}
