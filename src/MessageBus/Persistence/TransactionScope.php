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
     * The transaction object passed to handlers and persistence adapters.
     *
     * The returned object may lazily begin the underlying transaction when first used.
     * Transaction lifecycle is owned by this scope: callers must not commit or rollback
     * through the returned object.
     *
     * @var Tx
     */
    public object $transaction { get; }

    /**
     * Whether the underlying transaction has been begun by this scope.
     *
     * This flag remains true after a successful commit or rollback. Use it to
     * decide whether work must be joined to the transaction, not whether the
     * transaction is still active.
     */
    public bool $hasBegun { get; }

    /**
     * Begins the underlying transaction if it has not begun yet.
     *
     * This method is idempotent: after the first successful call, subsequent
     * calls must not begin nested transactions or otherwise change the scope
     * state.
     */
    public function ensureBegun(): void;

    /**
     * Commits the underlying transaction only if this scope has begun it.
     *
     * Calling this method before the transaction has begun must be a no-op.
     */
    public function commitIfBegun(): void;

    /**
     * Rolls back the underlying transaction only while it is active.
     *
     * Calling this method before the transaction has begun, or after it has
     * already been committed or rolled back, must be a no-op.
     */
    public function rollbackIfActive(): void;
}
