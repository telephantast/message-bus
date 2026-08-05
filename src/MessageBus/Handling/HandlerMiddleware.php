<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\HandlerContext;

/**
 * @api
 *
 * @template Tx of object = object
 */
interface HandlerMiddleware
{
    /**
     * @template T of object
     * @param T $message
     * @param TransactionScope<Tx> $txScope
     * @param HandlerPipeline<T, Tx> $pipeline
     */
    public function process(object $message, HandlerContext $context, TransactionScope $txScope, HandlerPipeline $pipeline): void;
}
