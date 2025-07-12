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
     * @param non-empty-string $endpoint
     * @param Handler<TMessage> $handler
     * @param list<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     */
    public function __construct(
        private string $endpoint,
        private Handler $handler,
        private array $middleware,
        private Envelope $envelope,
        private Context $context,
    ) {}

    /**
     * @param ?Envelope<TMessage> $newEnvelope
     * @return TResult
     */
    public function continue(?Context $newContext = null, ?Envelope $newEnvelope = null): mixed
    {
        $envelope = $newEnvelope ?? $this->envelope;
        $context = $newContext ?? $this->context;

        if ($this->middleware === []) {
            return $this->handler->handle($this->endpoint, $envelope, $context);
        }

        /** @var self<TResult, TMessage> */
        $pipeline = new self(
            endpoint: $this->endpoint,
            handler: $this->handler,
            middleware: \array_slice($this->middleware, offset: 1),
            envelope: $envelope,
            context: $context,
        );

        return $this->middleware[0]->handle($this->endpoint, $envelope, $context, $pipeline);
    }
}
