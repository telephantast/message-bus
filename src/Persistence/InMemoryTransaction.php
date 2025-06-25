<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Persistence\Outbox\Outbox;
use Thesis\MessageBus\Persistence\Outbox\OutboxAlreadyExists;
use Thesis\MessageBus\Persistence\Outbox\OutboxTransaction;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 * @implements OutboxTransaction<object>
 */
final class InMemoryTransaction implements OutboxTransaction
{
    private bool $closed = false;

    /**
     * @var array<non-empty-string, array<non-empty-string, Outbox>>
     */
    private array $outboxes = [];

    /**
     * @param \Closure(Outbox): bool $hasOutbox
     * @param \Closure(Outbox): void $setOutbox
     */
    public function __construct(
        private readonly \Closure $hasOutbox,
        private readonly \Closure $setOutbox,
    ) {}

    public self $wrappedTransaction { get => $this; }

    public function insertOutbox(Outbox $outbox): void
    {
        $this->ensureNotClosed();

        if (($this->hasOutbox)($outbox) || isset($this->outboxes[$outbox->incomingMessageId][$outbox->endpoint])) {
            throw new OutboxAlreadyExists();
        }

        $this->outboxes[$outbox->incomingMessageId][$outbox->endpoint] = $outbox;
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        foreach ($this->outboxes as $outboxes) {
            foreach ($outboxes as $outbox) {
                ($this->setOutbox)($outbox);
            }
        }

        $this->outboxes = [];
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
