<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\HandlerContext;

/**
 * @api
 *
 * @template Tx of object = object
 */
interface Middleware
{
    /**
     * @template T of object
     * @param T $message
     * @param Tx $transaction
     * @param Pipeline<T, Tx> $pipeline
     */
    public function process(object $message, HandlerContext $context, object $transaction, Pipeline $pipeline): void;
}
