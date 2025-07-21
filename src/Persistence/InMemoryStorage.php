<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Endpoint;

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

    public function beginTransaction(Endpoint $endpoint, string $incomingMessageId): Transaction
    {
        return new InMemoryTransaction(
            function (Outbox $outbox) use ($endpoint, $incomingMessageId): void {
                if (isset($this->outboxes[$endpoint->toString()][$incomingMessageId])) {
                    throw new OutboxAlreadyExists();
                }

                $this->outboxes[$endpoint->toString()][$incomingMessageId] = $outbox;
            },
        );
    }

    public function findOutbox(Endpoint $endpoint, string $incomingMessageId): ?Outbox
    {
        return $this->outboxes[$endpoint->toString()][$incomingMessageId] ?? null;
    }

    public function markOutboxDispatched(Endpoint $endpoint, string $incomingMessageId): void
    {
        $outbox = $this->findOutbox($endpoint, $incomingMessageId);

        if ($outbox !== null) {
            $this->outboxes[$endpoint->toString()][$incomingMessageId] = $outbox->toDispatched();
        }
    }
}
