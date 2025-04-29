<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Thesis\Message\Message;
use Thesis\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Thesis\MessageBus\HandlerRegistry\ContextHandlerRegistry;
use Thesis\MessageBus\HandlerRegistry\ScopedHandlerRegistry;
use Thesis\MessageBus\MessageId\MessageIdGenerator;
use Thesis\MessageBus\MessageId\RandomMessageIdGenerator;
use Thesis\MessageBus\Time\WallClock;

/**
 * @api
 */
final readonly class MessageBus
{
    public function __construct(
        private HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
        private ClockInterface $clock = new WallClock(),
    ) {}

    /**
     * @template TResult
     * @param Message<TResult> $message
     * @return TResult
     */
    public function dispatch(
        Message $message,
        PublishOptions $options = new PublishOptions(),
        ?Envelope $causation = null,
        ContextAttributes $attributes = new ContextAttributes(),
    ): mixed {
        $messageId = $options->messageId ?? $this->messageIdGenerator->generateMessageId();

        $envelope = new Envelope(
            message: $message,
            messageId: $messageId,
            causationId: $causation?->messageId,
            correlationId: $causation->correlationId ?? $causation->messageId ?? $messageId,
            timestamp: $this->clock->now(),
            headers: $options->headers,
            transportOptions: $options->transportOptions,
        );

        $context = new Context(
            messageBus: $this,
            envelope: $envelope,
            attributes: $attributes,
        );

        return $this->resolveHandlerRegistry($attributes)->get($message::class)->handle($context);
    }

    private function resolveHandlerRegistry(ContextAttributes $attributes): HandlerRegistry
    {
        $attribute = $attributes->get(ContextHandlerRegistry::class);

        if ($attribute !== null) {
            return $attribute->handlerRegistry;
        }

        if ($this->handlerRegistry instanceof ScopedHandlerRegistry) {
            $handlerRegistry = $this->handlerRegistry->scoped();
            $attributes->add(new ContextHandlerRegistry($handlerRegistry));

            return $handlerRegistry;
        }

        return $this->handlerRegistry;
    }
}
