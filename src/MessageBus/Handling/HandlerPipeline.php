<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\HandlerContext;

/**
 * @api
 *
 * @template T of object
 * @template Tx of object
 * @template-covariant TContinue of null = null
 */
final class HandlerPipeline
{
    /**
     * @template ST of object
     * @template STx of object
     * @param callable(ST, HandlerContext, STx): void $handler
     * @param iterable<HandlerMiddleware<STx>> $middleware
     * @return callable(ST, HandlerContext, STx): void
     */
    public static function wrap(callable $handler, iterable $middleware): callable
    {
        $middleware = iterator_to_array($middleware, preserve_keys: false);

        if ($middleware === []) {
            return $handler;
        }

        /** @phpstan-ignore argument.type */
        return static fn(object $msg, HandlerContext $ctx, object $tx) => new self($handler, $middleware, $msg, $ctx, $tx)->continue();
    }

    /**
     * @var non-negative-int
     */
    private int $middlewareOffset = 0;

    private bool $closed = false;

    /**
     * @param callable(T, HandlerContext, Tx): void $handler
     * @param non-empty-list<HandlerMiddleware<Tx>> $middleware
     * @param T $message
     * @param Tx $transaction
     */
    private function __construct(
        private readonly mixed $handler,
        private readonly array $middleware,
        private object $message,
        private readonly HandlerContext $context,
        private readonly object $transaction,
    ) {}

    /**
     * Must be called at most once per middleware; calling it again (e.g. to retry) throws.
     *
     * The call-once rule is also enforced statically: `@phpstan-this-out` rebinds `$this` to
     * `self<T, Tx, never>` after the call, so `TContinue` collapses to `never` and a second
     * `continue()` is typed as never-returning — PHPStan then reports the retry as unreachable code.
     *
     * `null` is merely a stand-in for `void`, which PHPStan cannot carry as a `@template` value.
     *
     * @param ?T $nextMessage pass only to replace the envelope downstream; omit to forward it unchanged
     * @return TContinue
     * @phpstan-this-out self<T, Tx, never>
     */
    public function continue(?object $nextMessage = null): null
    {
        if ($this->closed) {
            throw new \LogicException('Middleware must not call $next more than once (retrying is not allowed).');
        }

        if ($nextMessage !== null) {
            $this->message = $nextMessage;
        }

        try {
            $middleware = $this->middleware[$this->middlewareOffset] ?? null;

            if ($middleware !== null) {
                ++$this->middlewareOffset;

                $middleware->process($this->message, $this->context, $this->transaction, $this);

                /** @phpstan-ignore return.type */
                return null;
            }

            ($this->handler)($this->message, $this->context, $this->transaction);

            /** @phpstan-ignore return.type */
            return null;
        } finally {
            $this->closed = true;
        }
    }
}
