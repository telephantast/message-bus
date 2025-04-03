<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Thesis\Message\Message;
use Thesis\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
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
     * @param list<ContextAttribute> $attributes
     * @return TResult
     */
    public function dispatch(
        Message $message,
        PublishOptions $options = new PublishOptions(),
        ?Envelope $causation = null,
        array $attributes = [],
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

        return $this->handlerRegistry->get($message::class)->handle($context);
    }
}
