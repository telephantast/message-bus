<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
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
     * @param \SplQueue<Middleware> $middlewares
     */
    private function __construct(
        private readonly MessageContext $messageContext,
        private readonly Handler $handler,
        private \SplQueue $middlewares,
    ) {}

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param Handler<TTResult, TTMessage> $handler
     * @param MessageContext<TTResult, TTMessage> $messageContext
     * @param iterable<Middleware> $middlewares
     * @return TTResult
     */
    public static function handle(MessageContext $messageContext, Handler $handler, iterable $middlewares): mixed
    {
        /** @var \SplQueue<Middleware> */
        $middlewareQueue = new \SplQueue();

        foreach ($middlewares as $middleware) {
            $middlewareQueue->enqueue($middleware);
        }

        if ($middlewareQueue->isEmpty()) {
            return $handler->handle($messageContext);
        }

        return (new self($messageContext, $handler, $middlewareQueue))->continue();
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

        if (!$this->middlewares->isEmpty()) {
            return $this->middlewares->dequeue()->handle($this->messageContext, $this);
        }

        $this->handled = true;

        return $this->handler->handle($this->messageContext);
    }
}
