<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Outbox\Outbox;
use Thesis\MessageBus\Outbox\OutboxDoesNotExist;
use Thesis\MessageBus\Outbox\OutboxStorage;
use Thesis\MessageBus\Outbox\OutboxTransaction;

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

    public function findOutbox(string $endpoint, string $incomingMessageId): ?Outbox
    {
        return $this->outboxes[$endpoint][$incomingMessageId] ?? null;
    }

    private function hasOutbox(Outbox $outbox): bool
    {
        return isset($this->outboxes[$outbox->endpoint][$outbox->incomingMessageId]);
    }

    private function setOutbox(Outbox $outbox): void
    {
        $this->outboxes[$outbox->endpoint][$outbox->incomingMessageId] = $outbox;
    }

    public function completeOutbox(string $endpoint, string $incomingMessageId): void
    {
        $outbox = $this->findOutbox($endpoint, $incomingMessageId);

        if ($outbox === null) {
            throw new OutboxDoesNotExist();
        }

        $this->setOutbox($outbox->toEmpty());
    }
}
