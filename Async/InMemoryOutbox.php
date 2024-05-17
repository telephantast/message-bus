<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
final class InMemoryOutbox implements Outbox
{
    /**
     * @var array<non-empty-string, Envelope>
     */
    private array $envelopes = [];

    public function add(Envelope $envelope): void
    {
        $this->envelopes[$envelope->getMessageId()] = $envelope;
    }

    public function all(): array
    {
        return array_values($this->envelopes);
    }

    public function remove(string $messageId): void
    {
        unset($this->envelopes[$messageId]);
    }
}
