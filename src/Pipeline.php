<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    private bool $started = false;

    private bool $handled = false;

    /**
     * @param MessageContext<TResult, TMessage> $messageContext
     * @param Handler<TResult, TMessage> $handler
     * @param \Iterator<Middleware> $middlewares
     */
    private function __construct(
        private readonly MessageContext $messageContext,
        private readonly Handler $handler,
        private readonly \Iterator $middlewares,
    ) {}

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param MessageContext<TTResult, TTMessage> $messageContext
     * @param Handler<TTResult, TTMessage> $handler
     * @param iterable<Middleware> $middlewares
     * @return TTResult
     */
    public static function handle(MessageContext $messageContext, Handler $handler, iterable $middlewares): mixed
    {
        if (\is_array($middlewares)) {
            $middlewares = new \ArrayIterator($middlewares);
        } else {
            $middlewares = new \IteratorIterator($middlewares);
            $middlewares->rewind();
        }

        if (!$middlewares->valid()) {
            return $handler->handle($messageContext);
        }

        return (new self($messageContext, $handler, $middlewares))->continue();
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
            throw new \LogicException('Pipeline fully handled');
        }

        if ($this->started) {
            $this->middlewares->next();
        } else {
            $this->started = true;
        }

        if ($this->middlewares->valid()) {
            /** @psalm-suppress PossiblyNullReference */
            return $this->middlewares->current()->handle($this->messageContext, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->messageContext);
    }
}
