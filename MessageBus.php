<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\HandlerRegistry\ArrayHandlerRegistry;
use Telephantast\MessageBus\HandlerRegistry\EnsureNonEventMessageHasHandler;

/**
 * @api
 */
final readonly class MessageBus
{
    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private HandlerRegistry $handlerRegistry = new EnsureNonEventMessageHasHandler(new ArrayHandlerRegistry()),
        private iterable $middlewares = [],
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelope
     * @return TResult
     */
    public function dispatch(Envelope|Message $messageOrEnvelope): mixed
    {
        return $this->handleContext($this->startContext($messageOrEnvelope));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelope
     * @return MessageContext<TResult, TMessage>
     */
    public function startContext(Envelope|Message $messageOrEnvelope): MessageContext
    {
        return new MessageContext($this, $messageOrEnvelope);
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return TResult
     */
    public function handleContext(MessageContext $messageContext): mixed
    {
        return Pipeline::handle(
            messageContext: $messageContext,
            handlerOrRegistry: $this->handlerRegistry,
            middlewares: $this->middlewares,
        );
    }
}
