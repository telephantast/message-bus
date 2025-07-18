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
    public self $wrappedTransaction { get => $this; }

    private bool $closed = false;

    private ?Outbox $outbox = null;

    /**
     * @param \Closure(Outbox): void $insertOutbox
     */
    public function __construct(
        private readonly \Closure $insertOutbox,
    ) {}

    public function recordOutbox(Outbox $outbox): void
    {
        $this->ensureNotClosed();

        if ($this->outbox !== null) {
            throw new OutboxAlreadyExists();
        }

        $this->outbox = $outbox;
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        if ($this->outbox !== null) {
            ($this->insertOutbox)($this->outbox);
        }

        $this->closed = true;
    }

    public function rollback(): void
    {
        $this->ensureNotClosed();

        $this->outbox = null;
        $this->closed = true;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new TransactionClosed();
        }
    }
}
