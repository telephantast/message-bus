<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @implements Storage<object>
 */
final class InMemoryStorage implements Storage
{
    /**
     * @var array<non-empty-string, array<non-empty-string, Outbox>>
     */
    private array $outboxes = [];

    public function beginTransaction(): Transaction
    {
        return new InMemoryTransaction($this->insertOutbox(...));
    }

    public function findOutbox(string $endpoint, string $messageId): ?Outbox
    {
        return $this->outboxes[$endpoint][$messageId] ?? null;
    }

    private function insertOutbox(Outbox $outbox): void
    {
        if ($this->hasOutbox($outbox)) {
            throw new OutboxAlreadyExists();
        }

        $this->setOutbox($outbox);
    }

    public function updateOutbox(Outbox $outbox): void
    {
        if (!$this->hasOutbox($outbox)) {
            throw new OutboxDoesNotExist();
        }

        $this->setOutbox($outbox);
    }

    private function hasOutbox(Outbox $outbox): bool
    {
        return isset($this->outboxes[$outbox->endpoint][$outbox->messageId]);
    }

    private function setOutbox(Outbox $outbox): void
    {
        $this->outboxes[$outbox->endpoint][$outbox->messageId] = $outbox;
    }
}
