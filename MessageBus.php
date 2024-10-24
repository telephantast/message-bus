<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;
use Telephantast\MessageBus\HandlerRegistry\ArrayHandlerRegistry;

/**
 * @api
 */
final class MessageBus
{
    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private readonly HandlerRegistry $handlerRegistry = new ArrayHandlerRegistry(),
        private readonly iterable $middlewares = [],
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelope
     * @return (TResult is void ? null : TResult)
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
        return MessageContext::start($this, $messageOrEnvelope);
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param MessageContext<TResult, TMessage> $messageContext
     * @return (TResult is void ? null : TResult)
     */
    public function handleContext(MessageContext $messageContext): mixed
    {
        return Pipeline::handle(
            messageContext: $messageContext,
            handler: $this->handlerRegistry->get($messageContext->getMessageClass()),
            middlewares: $this->middlewares,
        );
    }
}
