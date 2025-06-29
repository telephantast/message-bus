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
     * @param ?Envelope<TMessage> $newEnvelope
     * @return Result<TResult>
     */
    public function continue(?Context $newContext = null, ?Envelope $newEnvelope = null): Result
    {
        $envelope = $newEnvelope ?? $this->envelope;
        $context = $newContext ?? $this->context;

        if ($this->middleware === []) {
            return $this->handler->handle($envelope, $context);
        }

        /** @var self<TResult, TMessage> */
        $pipeline = new self(
            handler: $this->handler,
            middleware: \array_slice($this->middleware, offset: 1),
            envelope: $envelope,
            context: $context,
        );

        return $this->middleware[0]->handle($envelope, $context, $pipeline);
    }
}
