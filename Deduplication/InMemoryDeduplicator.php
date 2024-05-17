<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Deduplication;

/**
 * @api
 */
final class InMemoryDeduplicator implements Deduplicator
{
    /**
     * @var array<non-empty-string, non-empty-array<non-empty-string, true>>
     */
    private array $handled = [];

    public function isHandled(string $queue, string $messageId): bool
    {
        if (isset($this->handled[$queue][$messageId])) {
            return true;
        }

        $this->handled[$queue][$messageId] = true;

        return false;
    }
}
