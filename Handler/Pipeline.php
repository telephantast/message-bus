<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\HandlerRegistry;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    private bool $handled = false;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Handler<TResult, TMessage> $handler
     * @param array<Middleware> $middlewares
     */
    private function __construct(
        private readonly MessageContext $messageContext,
        private readonly Handler $handler,
        private array $middlewares,
    ) {}

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param Handler<TTResult, TTMessage>|HandlerRegistry $handlerOrRegistry
     * @param iterable<Middleware> $middlewares
     * @param MessageContext<TTResult, TTMessage> $messageContext
     * @return TTResult
     */
    public static function handle(MessageContext $messageContext, Handler|HandlerRegistry $handlerOrRegistry, iterable $middlewares = []): mixed
    {
        if (!\is_array($middlewares)) {
            $middlewares = iterator_to_array($middlewares, preserve_keys: false);
        }

        if ($handlerOrRegistry instanceof HandlerRegistry) {
            $handlerOrRegistry = $handlerOrRegistry->get($messageContext->getMessageClass());

            if ($handlerOrRegistry === null) {
                /** @phpstan-ignore return.type */
                return null;
            }
        }

        if ($middlewares === []) {
            return $handlerOrRegistry->handle($messageContext);
        }

        return (new self($messageContext, $handlerOrRegistry, $middlewares))->continue();
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->handler->id();
    }

    /**
     * @return TResult
     */
    public function continue(): mixed
    {
        if ($this->handled) {
            throw new \LogicException('Pipeline fully handled.');
        }

        $nextMiddleware = array_shift($this->middlewares);

        if ($nextMiddleware !== null) {
            return $nextMiddleware->handle($this->messageContext, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->messageContext);
    }
}
