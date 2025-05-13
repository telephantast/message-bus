<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 * @implements Transaction<object>
 */
final class InMemoryTransaction implements Transaction
{
    private bool $closed = false;

    /**
     * @param \Closure(Outbox): void $insertOutbox
     */
    public function __construct(
        private readonly \Closure $insertOutbox,
    ) {}

    public self $wrappedTransaction { get => $this; }

    public function commit(Outbox $outbox): void
    {
        $this->ensureNotClosed();
        $this->closed = true;

        ($this->insertOutbox)($outbox);
    }

    public function rollback(): void
    {
        $this->ensureNotClosed();
        $this->closed = true;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new TransactionClosed();
        }
    }
}
