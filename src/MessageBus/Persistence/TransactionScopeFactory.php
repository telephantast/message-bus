<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface TransactionScopeFactory
{
    /**
     * Create a new transaction scope for one message handling attempt.
     * The returned scope must not start the underlying transaction eagerly.
     *
     * @return TransactionScope<Tx>
     */
    public function create(): TransactionScope;
}
