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

    /**
     * @var array<non-empty-string, Outbox>
     */
    private array $outboxes = [];

    /**
     * @param \Closure(non-empty-array<Outbox>): void $insertOutbox
     */
    public function __construct(
        private readonly \Closure $insertOutbox,
    ) {}

    public function recordOutboxes(array $outboxes): void
    {
        $this->ensureNotClosed();

        foreach ($outboxes as $outbox) {
            if (isset($this->outboxes[$outbox->incomingMessageId])) {
                throw new OutboxAlreadyExists();
            }
        }

        foreach ($outboxes as $outbox) {
            $this->outboxes[$outbox->incomingMessageId] = $outbox;
        }
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        if ($this->outboxes !== []) {
            ($this->insertOutbox)($this->outboxes);
        }

        $this->closed = true;
    }

    public function rollback(): void
    {
        $this->ensureNotClosed();

        $this->outboxes = [];
        $this->closed = true;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new TransactionClosed();
        }
    }
}
