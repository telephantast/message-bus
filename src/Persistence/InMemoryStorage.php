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

    public function beginTransaction(Endpoint $endpoint): Transaction
    {
        $endpoint = $endpoint->toString();

        return new InMemoryTransaction(
            function (array $outboxes) use ($endpoint): void {
                foreach ($outboxes as $outbox) {
                    if (isset($this->outboxes[$endpoint][$outbox->incomingMessageId])) {
                        throw new OutboxAlreadyExists();
                    }
                }

                foreach ($outboxes as $outbox) {
                    $this->outboxes[$endpoint][$outbox->incomingMessageId] = $outbox;
                }
            },
        );
    }

    public function findOutboxes(Endpoint $endpoint, array $incomingMessageIds): array
    {
        $endpoint = $endpoint->toString();
        $outboxes = [];

        foreach ($incomingMessageIds as $messageId) {
            if (isset($this->outboxes[$endpoint][$messageId])) {
                $outboxes[] = $this->outboxes[$endpoint][$messageId];
            }
        }

        return $outboxes;
    }

    public function markOutboxesDispatched(Endpoint $endpoint, array $incomingMessageIds): void
    {
        $endpoint = $endpoint->toString();

        foreach ($incomingMessageIds as $messageId) {
            $outbox = $this->outboxes[$endpoint][$messageId] ?? null;

            if ($outbox !== null) {
                $this->outboxes[$endpoint][$messageId] = $outbox->toDispatched();
            }
        }
    }
}
