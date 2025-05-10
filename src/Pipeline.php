<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Handling\HandleContext;
use Thesis\MessageBus\Handling\Handler;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 * @template TMessage of Message
 * @template TTransaction of Transaction
 */
final class Pipeline
{
    /**
     * @template TMethodMessage of Message
     * @template TMethodTransaction of Transaction
     * @param Handler<TMethodMessage, TMethodTransaction> $handler
     * @param iterable<Middleware> $middleware
     * @param Envelope<TMethodMessage> $envelope
     * @param HandleContext<TMethodTransaction> $context
     */
    public static function handle(Handler $handler, iterable $middleware, Envelope $envelope, HandleContext $context): void
    {
        if (\is_array($middleware)) {
            $middleware = new \ArrayIterator($middleware);
        } elseif (!$middleware instanceof \Iterator) {
            $middleware = new \IteratorIterator($middleware);
        }

        $middleware->rewind();

        if (!$middleware->valid()) {
            $handler->handle($envelope, $context);

            return;
        }

        new self($handler, $middleware, $envelope, $context)->continue();
    }

    /**
     * @return non-empty-string
     */
    public string $handlerId { get => $this->handler->id; }

    private bool $called = false;

    /**
     * @param Handler<TMessage, TTransaction> $handler
     * @param \Iterator<Middleware> $middleware
     * @param Envelope<TMessage> $envelope
     * @param HandleContext<TTransaction> $context
     */
    public function __construct(
        private readonly Handler $handler,
        private readonly \Iterator $middleware,
        private readonly Envelope $envelope,
        private readonly HandleContext $context,
    ) {}

    /**
     * @param ?Envelope<TMessage> $envelope
     */
    public function continue(?Envelope $envelope = null): void
    {
        if ($this->called) {
            throw new \LogicException('Cannot call continue twice');
        }

        $this->called = true;
        $nextEnvelope = $envelope ?? $this->envelope;

        if (!$this->middleware->valid()) {
            $this->handler->handle($nextEnvelope, $this->context);

            return;
        }

        $middleware = $this->middleware->current();
        $this->middleware->next();

        $middleware->handle($nextEnvelope, $this->context, new self(
            handler: $this->handler,
            middleware: $this->middleware,
            envelope: $nextEnvelope,
            context: $this->context,
        ));
    }
}
