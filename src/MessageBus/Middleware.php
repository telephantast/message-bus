<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template Tx of object = object
 */
interface Middleware
{
    /**
     * @template T of object
     * @param Envelope<T> $envelope
     * @param HandlerContext<Tx> $context
     * @param callable(Envelope<T>, HandlerContext<Tx>): void $handler
     */
    public function process(Envelope $envelope, HandlerContext $context, callable $handler): void;
}
