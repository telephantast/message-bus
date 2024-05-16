<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Deduplication;

/**
 * @api
 */
interface Deduplicator
{
    /**
     * @param non-empty-string $handlerId
     * @param non-empty-string $messageId
     */
    public function isHandled(string $handlerId, string $messageId): bool;
}
