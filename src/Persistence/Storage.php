<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TTransaction of object
 */
interface Storage
{
    /**
     * @return Transaction<TTransaction>
     */
    public function beginTransaction(): Transaction;
}
