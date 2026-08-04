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
     * @return TransactionScope<Tx>
     */
    public function create(): TransactionScope;
}
