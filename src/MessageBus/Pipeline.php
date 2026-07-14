<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template T of object
 * @template Tx of object
 */
final class Pipeline
{
    /**
     * @template FT of object
     * @template FTx of object
     * @param callable(Envelope<FT>, HandlerContext<FTx>): void $handler
     * @param iterable<Middleware<FTx>> $middleware
     * @return callable(Envelope<FT>, HandlerContext<FTx>): void
     */
    public static function wrap(callable $handler, iterable $middleware): callable
    {
        $middleware = iterator_to_array($middleware, preserve_keys: false);

        if ($middleware === []) {
            return $handler;
        }

        return static function (Envelope $envelope, HandlerContext $context) use ($handler, $middleware): void {
            /**
             * @var Envelope<FT> $envelope
             * @var HandlerContext<FTx> $context
             */
            new self($handler, $middleware, $envelope, $context)->continue();
        };
    }

    /**
     * @var non-negative-int
     */
    private int $middlewareOffset = 0;

    private bool $closed = false;

    /**
     * @param callable(Envelope<T>, HandlerContext<Tx>): void $handler
     * @param non-empty-list<Middleware<Tx>> $middleware
     * @param Envelope<T> $envelope
     * @param HandlerContext<Tx> $context
     */
    private function __construct(
        private readonly mixed $handler,
        private readonly array $middleware,
        private Envelope $envelope,
        private HandlerContext $context,
    ) {}

    /**
     * @param ?Envelope<T> $envelope
     * @param ?HandlerContext<Tx> $context
     */
    public function continue(?Envelope $envelope = null, ?HandlerContext $context = null): void
    {
        if ($this->closed) {
            throw new \LogicException('Middleware must not call $next more than once (retrying is not allowed).');
        }

        if ($envelope !== null) {
            $this->envelope = $envelope;
        }

        if ($context !== null) {
            $this->context = $context;
        }

        try {
            $middleware = $this->middleware[$this->middlewareOffset] ?? null;

            if ($middleware !== null) {
                ++$this->middlewareOffset;
                $middleware->process($this->envelope, $this->context, $this);

                return;
            }

            ($this->handler)($this->envelope, $this->context);
        } finally {
            $this->closed = true;
        }
    }
}
