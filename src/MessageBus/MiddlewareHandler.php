<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant T of object
 * @template-contravariant Tx of object
 */
final readonly class MiddlewareHandler
{
    /**
     * @template ST of object
     * @template STx of object
     * @param callable(Envelope<ST>, HandlerContext<STx>): void $handler
     * @param iterable<Middleware<STx>> $middleware
     * @return callable(Envelope<ST>, HandlerContext<STx>): void
     */
    public static function stack(callable $handler, iterable $middleware): callable
    {
        foreach (array_reverse(iterator_to_array($middleware, preserve_keys: false)) as $oneMiddleware) {
            $handler = new self($oneMiddleware, $handler);
        }

        return $handler;
    }

    /**
     * @param Middleware<Tx> $middleware
     * @param callable(Envelope<T>, HandlerContext<Tx>): void $handler
     */
    public function __construct(
        private Middleware $middleware,
        private mixed $handler,
    ) {}

    /**
     * @param Envelope<T> $envelope
     * @param HandlerContext<Tx> $context
     */
    public function __invoke(Envelope $envelope, HandlerContext $context): void
    {
        $this->middleware->process($envelope, $context, $this->handler);
    }
}
