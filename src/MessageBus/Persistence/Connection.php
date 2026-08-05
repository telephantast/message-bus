<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface Connection
{
    /**
     * @return Transaction<Tx>
     */
    public function begin(): Transaction;
}
