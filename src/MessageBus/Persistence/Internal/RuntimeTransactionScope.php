<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Internal;

use Thesis\MessageBus\Handling\TransactionScope;
use Thesis\MessageBus\Persistence\Connection;
use Thesis\MessageBus\Persistence\Transaction;
use Thesis\Sync\Once;

/**
 * @internal
 *
 * @template-covariant Tx of object
 * @implements TransactionScope<Tx>
 */
final class RuntimeTransactionScope implements TransactionScope
{
    /**
     * @param Connection<Tx> $connection
     */
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * @var ?Once<Transaction<Tx>>
     */
    private ?Once $transaction = null;

    public private(set) bool $hasBegun = false;

    public private(set) bool $closed = false;

    public object $handle {
        get => $this->begin()->handle;
    }

    /**
     * @return Transaction<Tx>
     */
    public function begin(): Transaction
    {
        $this->ensureNotClosed();

        $transaction = ($this->transaction ??= new Once($this->connection->begin(...)))->await();

        $this->hasBegun = true;

        return $transaction;
    }

    public function commit(): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->transaction?->await()->commit();
        } finally {
            $this->closed = true;
            $this->transaction = null;
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->transaction?->await()->rollback();
        } finally {
            $this->closed = true;
            $this->transaction = null;
        }
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new \LogicException('This transaction scope has already been closed.');
        }
    }
}
