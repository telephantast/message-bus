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
    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param Context<TTResult, TTMessage> $context
     * @param Handler<TTResult, TTMessage> $handler
     * @param iterable<Middleware> $middleware
     * @return TTResult
     */
    public static function handle(Handler $handler, iterable $middleware, Context $context): mixed
    {
        if (\is_array($middleware)) {
            $middleware = new \ArrayIterator($middleware);
        } elseif (!$middleware instanceof \Iterator) {
            $middleware = new \IteratorIterator($middleware);
        }

        $middleware->rewind();

        if (!$middleware->valid()) {
            return $handler->handle($context);
        }

        return (new self($handler, $middleware, $context))->continue();
    }

    private bool $started = false;

    private bool $handled = false;

    /**
     * @param Handler<TResult, TMessage> $handler
     * @param \Iterator<Middleware> $middleware
     * @param Context<TResult, TMessage> $context
     */
    private function __construct(
        private readonly Handler $handler,
        private readonly \Iterator $middleware,
        private readonly Context $context,
    ) {}

    /**
     * @return non-empty-string
     */
    public function handlerId(): string
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
            $this->middleware->next();
        } else {
            $this->started = true;
        }

        if ($this->middleware->valid()) {
            /** @psalm-suppress PossiblyNullReference */
            return $this->middleware->current()->handle($this->context, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->context);
    }
}
