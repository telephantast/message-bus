<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
interface OutboxRepository
{
    /**
     * @param non-empty-string $outboxId
     * @param non-empty-string $messageId
     */
    public function add(string $outboxId, string $messageId, Envelope $envelope): void;

    /**
     * @return list<Envelope>
     */
    public function get(string $outboxId): array;

    /**
     * @param non-empty-string $messageId
     */
    public function remove(string $messageId): void;
}
