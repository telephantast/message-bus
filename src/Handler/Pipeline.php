<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

/**
 * @api
 * @template-covariant TResult
 * @template TMessage of object
 * @template TTransaction of object
 */
final class Pipeline
{
    /**
     * @param callable(Envelope<TMessage>, Context<object, TTransaction>): TResult $handler
     * @param list<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     * @param Context<object, TTransaction> $context
     */
    public function __construct(
        private readonly mixed $handler,
        private array $middleware,
        private Envelope $envelope,
        private readonly Context $context,
    ) {}

    /**
     * @todo allow to override envelope
     * @todo revert middleware order for speed?
     * @return TResult
     */
    public function continue(): mixed
    {
        if ($this->middleware === []) {
            return ($this->handler)($this->envelope, $this->context);
        }

        $copy = clone $this;
        $copy->middleware = \array_slice($this->middleware, offset: 1);

        return $this->middleware[0]->handle($this->envelope, $this->context, $copy);
    }
}
