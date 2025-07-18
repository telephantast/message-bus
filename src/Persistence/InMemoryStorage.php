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

    public function setup(): void {}

    public function beginTransaction(string $endpoint, string $incomingMessageId): Transaction
    {
        return new InMemoryTransaction(
            function (Outbox $outbox) use ($endpoint, $incomingMessageId): void {
                if (isset($this->outboxes[$endpoint][$incomingMessageId])) {
                    throw new OutboxAlreadyExists();
                }

                $this->outboxes[$endpoint][$incomingMessageId] = $outbox;
            },
        );
    }

    public function findOutbox(string $endpoint, string $incomingMessageId): ?Outbox
    {
        return $this->outboxes[$endpoint][$incomingMessageId] ?? null;
    }

    public function markOutboxDispatched(string $endpoint, string $incomingMessageId): void
    {
        $outbox = $this->findOutbox($endpoint, $incomingMessageId);

        if ($outbox !== null) {
            $this->outboxes[$endpoint][$incomingMessageId] = $outbox->toDispatched();
        }
    }
}
