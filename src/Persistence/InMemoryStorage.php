<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 */
final class InMemoryStorage implements Storage
{
    /**
     * @var array<non-empty-string, array<non-empty-string, Outbox>>
     */
    private array $outboxes = [];

    public function setup(): void {}

    public function beginTransaction(): Transaction
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

    public function markOutboxSent(string $endpoint, string $incomingMessageId): void
    {
        $outbox = $this->findOutbox($endpoint, $incomingMessageId);

        if ($outbox === null) {
            throw new OutboxDoesNotExist();
        }

        $this->setOutbox($outbox->toEmpty());
    }
}
