<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Persistence\Outbox\Outbox;
use Thesis\MessageBus\Persistence\Outbox\OutboxDoesNotExist;
use Thesis\MessageBus\Persistence\Outbox\OutboxStorage;
use Thesis\MessageBus\Persistence\Outbox\OutboxTransaction;

/**
 * @api
 * @implements OutboxStorage<object>
 */
final class InMemoryStorage implements OutboxStorage
{
    /**
     * @var array<non-empty-string, array<non-empty-string, Outbox>>
     */
    private array $outboxes = [];

    public function beginTransaction(): OutboxTransaction
    {
        return new InMemoryTransaction(
            hasOutbox: $this->hasOutbox(...),
            setOutbox: $this->setOutbox(...),
        );
    }

    public function findOutbox(string $incomingMessageId, string $endpoint): ?Outbox
    {
        return $this->outboxes[$incomingMessageId][$endpoint] ?? null;
    }

    private function hasOutbox(Outbox $outbox): bool
    {
        return isset($this->outboxes[$outbox->incomingMessageId][$outbox->endpoint]);
    }

    private function setOutbox(Outbox $outbox): void
    {
        $this->outboxes[$outbox->incomingMessageId][$outbox->endpoint] = $outbox;
    }

    public function updateOutbox(Outbox $outbox): void
    {
        if ($this->hasOutbox($outbox)) {
            throw new OutboxDoesNotExist();
        }

        $this->setOutbox($outbox);
    }
}
