<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @api
 * @template-covariant TResult = mixed
 * @template-covariant TMessage of Message<TResult> = Message<mixed>
 */
final readonly class Context
{
    /**
     * @param Envelope<TResult, TMessage> $envelope
     */
    public function __construct(
        private MessageBus $messageBus,
        public Envelope $envelope,
        public ContextAttributes $attributes = new ContextAttributes(),
    ) {}

    /**
     * @template TDispatchResult
     * @param Message<TDispatchResult> $message
     * @return TDispatchResult
     */
    public function dispatch(Message $message, PublishOptions $options = new PublishOptions()): mixed
    {
        return $this->messageBus->dispatch(
            message: $message,
            options: $options,
            causation: $this->envelope,
            attributes: $this->attributes->child(),
        );
    }
}
