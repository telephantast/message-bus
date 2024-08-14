<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

/**
 * @api
 */
final class InMemoryOutboxStorage implements OutboxStorage
{
    /**
     * @var array<string, array<non-empty-string, Outbox>>
     */
    private array $queueToMessageIdToOutbox = [];

    public function get(?string $queue, string $messageId): ?Outbox
    {
        return $this->queueToMessageIdToOutbox[$queue ?? ''][$messageId] ?? null;
    }

    public function create(?string $queue, string $messageId, Outbox $outbox): void
    {
        $queue ??= '';

        if (isset($this->queueToMessageIdToOutbox[$queue][$messageId])) {
            throw new OutboxAlreadyExists();
        }

        $this->queueToMessageIdToOutbox[$queue][$messageId] = $outbox;
    }

    public function empty(?string $queue, string $messageId): void
    {
        $this->queueToMessageIdToOutbox[$queue ?? ''][$messageId] = new Outbox();
    }
}
