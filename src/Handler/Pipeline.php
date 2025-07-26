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
     * @var non-negative-int
     */
    private int $middlewareIndex = 0;

    /**
     * @param callable(Envelope<TMessage>, Context<object, TTransaction>): TResult $handler
     * @param non-empty-list<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     * @param Context<object, TTransaction> $context
     */
    public function __construct(
        private readonly mixed $handler,
        private readonly array $middleware,
        private Envelope $envelope,
        private readonly Context $context,
    ) {}

    /**
     * @return TResult
     */
    public function continue(?Envelope $envelope = null): mixed
    {
        $envelope ??= $this->envelope;

        if (!isset($this->middleware[$this->middlewareIndex])) {
            return ($this->handler)($envelope, $this->context);
        }

        $pipeline = clone $this;

        ++$pipeline->middlewareIndex;
        $pipeline->envelope = $envelope;

        return $this->middleware[$this->middlewareIndex]->handle($envelope, $this->context, $pipeline);
    }
}
