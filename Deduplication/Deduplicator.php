<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Deduplication;

/**
 * @api
 */
interface Deduplicator
{
    /**
     * @param non-empty-string $queue
     * @param non-empty-string $messageId
     */
    public function isHandled(string $queue, string $messageId): bool;
}
