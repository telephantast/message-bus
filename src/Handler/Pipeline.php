<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;

/**
 * @api
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 */
final readonly class Pipeline
{
    /**
     * @param Handler<TMessage> $handler
     * @param list<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     */
    public function __construct(
        private Handler $handler,
        private array $middleware,
        private Envelope $envelope,
        private Context $context,
    ) {}

    /**
     * @return TResult
     */
    public function continue(): mixed
    {
        if ($this->middleware === []) {
            return $this->handler->handle($this->envelope, $this->context);
        }

        /** @var self<TResult, TMessage> */
        $pipeline = new self(
            handler: $this->handler,
            middleware: \array_slice($this->middleware, offset: 1),
            envelope: $this->envelope,
            context: $this->context,
        );

        return $this->middleware[0]->handle($this->envelope, $this->context, $pipeline);
    }
}
