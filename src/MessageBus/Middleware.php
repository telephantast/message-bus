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
     * @param Pipeline<T, Tx> $pipeline
     */
    public function process(Envelope $envelope, HandlerContext $context, Pipeline $pipeline): void;
}
