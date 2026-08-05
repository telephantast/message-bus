<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface Transaction
{
    /**
     * The persistence-specific transaction handle passed to handlers and adapters.
     *
     * @var Tx
     */
    public object $handle { get; }

    public function commit(): void;

    public function rollback(): void;
}
