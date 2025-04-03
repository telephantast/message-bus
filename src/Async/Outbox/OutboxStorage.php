<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

/**
 * @api
 */
interface OutboxStorage
{
    /**
     * @param non-empty-string $messageId
     */
    public function get(string $messageId, string $queue): ?Outbox;

    /**
     * @throws OutboxAlreadyExists
     */
    public function insert(Outbox $outbox): void;

    public function update(Outbox $outbox): void;
}
