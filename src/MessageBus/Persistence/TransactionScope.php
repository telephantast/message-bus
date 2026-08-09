<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface TransactionScope
{
    /**
     * The persistence-specific transaction handle passed to handlers and adapters.
     * Prefer starting the underlying transaction lazily on first real use.
     *
     * @var Tx
     */
    public object $transaction { get; }

    /**
     * Whether the underlying transaction has been started.
     */
    public bool $hasBegun { get; }

    /**
     * Start the underlying transaction if needed.
     */
    public function begin(): void;

    /**
     * Commit the transaction if it has begun and close the scope.
     */
    public function commit(): void;

    /**
     * Roll back the transaction if it has begun and close the scope.
     */
    public function rollback(): void;
}
