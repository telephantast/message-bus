<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Pipeline;

/**
 * @internal
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 * @template TTransaction of object
 */
final readonly class Handler
{
    /**
     * @param non-empty-string $id
     * @param callable(Envelope<TMessage>, Context<TTransaction>): TResult $handler
     * @param list<Middleware> $middleware
     */
    public function __construct(
        private string $id,
        private mixed $handler,
        private array $middleware,
    ) {}

    /**
     * @param Envelope<TMessage> $envelope
     * @param Context<TTransaction> $context
     * @return TResult
     */
    public function handle(Envelope $envelope, Context $context): mixed
    {
        if ($this->middleware === []) {
            return ($this->handler)($envelope, $context);
        }

        return new Pipeline($this->id, $this->handler, $this->middleware, $envelope, $context)->continue();
    }
}
