<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Outbox
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     * @param list<Envelope<Command>> $commands
     * @param list<Envelope<Event>> $events
     */
    public function __construct(
        public string $endpoint,
        public string $incomingMessageId,
        public array $commands = [],
        public array $events = [],
    ) {}

    public function toEmpty(): self
    {
        return new self(
            endpoint: $this->endpoint,
            incomingMessageId: $this->incomingMessageId,
        );
    }
}
