<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

/**
 * @api
 */
final class InMemoryOutboxStorage implements OutboxStorage
{
    /**
     * @var array<non-empty-string, array<string, Outbox>>
     */
    private array $envelopes = [];

    public function get(string $messageId, string $queue): ?Outbox
    {
        return $this->envelopes[$messageId][$queue] ?? null;
    }

    public function insert(Outbox $outbox): void
    {
        if (isset($this->envelopes[$outbox->messageId][$outbox->queue])) {
            throw new OutboxAlreadyExists();
        }

        $this->envelopes[$outbox->messageId][$outbox->queue] = $outbox;
    }

    public function update(Outbox $outbox): void
    {
        $this->envelopes[$outbox->messageId][$outbox->queue] = $outbox;
    }
}
