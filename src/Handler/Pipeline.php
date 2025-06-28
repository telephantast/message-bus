<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;

/**
 * @api
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    /**
     * @template THandleResult
     * @template THandleMessage of Message<THandleResult>
     * @template THandlerMessage of Message
     * @param Handler<THandlerMessage> $handler
     * @param iterable<Middleware> $middleware
     * @param Envelope<THandleMessage&THandlerMessage> $envelope
     * @return Result<THandleResult>
     */
    public static function handle(Handler $handler, iterable $middleware, Envelope $envelope, Context $context): Result
    {
        if (\is_array($middleware)) {
            $middleware = new \ArrayIterator($middleware);
        } elseif (!$middleware instanceof \Iterator) {
            $middleware = new \IteratorIterator($middleware);
        }

        $middleware->rewind();

        if (!$middleware->valid()) {
            return $handler->handle($envelope, $context);
        }

        /** @var self<THandleResult, THandleMessage> */
        $pipeline = new self($handler, $middleware, $envelope, $context); /** @phpstan-ignore argument.type */

        return $pipeline->continue();
    }

    private bool $called = false;

    /**
     * @param Handler<TMessage> $handler
     * @param \Iterator<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     */
    public function __construct(
        private readonly Handler $handler,
        private readonly \Iterator $middleware,
        private readonly Envelope $envelope,
        private readonly Context $context,
    ) {}

    /**
     * @param ?Envelope<TMessage> $newEnvelope
     * @return Result<TResult>
     */
    public function continue(?Context $newContext = null, ?Envelope $newEnvelope = null): Result
    {
        if ($this->called) {
            throw new \LogicException('Cannot call continue twice');
        }

        $this->called = true;
        $envelope = $newEnvelope ?? $this->envelope;
        $context = $newContext ?? $this->context;

        if (!$this->middleware->valid()) {
            return $this->handler->handle($envelope, $context);
        }

        $middleware = $this->middleware->current();
        $this->middleware->next();

        /** @var self<TResult, TMessage> */
        $pipeline = new self(
            handler: $this->handler,
            middleware: $this->middleware,
            envelope: $envelope,
            context: $context,
        );

        return $middleware->handle($envelope, $context, $pipeline);
    }
}
