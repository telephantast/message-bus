<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

/**
 * @api
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 */
final class Pipeline
{
    /**
     * @param non-empty-string $handlerId
     * @param callable(Envelope<TMessage>, Context): TResult $handler
     * @param list<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     */
    public function __construct(
        public readonly string $handlerId,
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
