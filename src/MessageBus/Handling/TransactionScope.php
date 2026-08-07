<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface TransactionScope
{
    /**
     * The persistence-specific transaction handle available to the handler.
     * Implementations may begin the underlying transaction when this handle is first used.
     * Concurrent coroutine access must observe the same transaction handle and must not
     * start more than one underlying transaction for the same scope.
     *
     * @var Tx
     */
    public object $handle { get; }
}
