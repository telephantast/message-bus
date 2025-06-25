<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Outbox;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Outbox
{
    /**
     * @param non-empty-string $incomingMessageId
     * @param non-empty-string $endpoint
     * @param list<Envelope<Command>> $commands
     * @param list<Envelope<Event>> $events
     */
    public function __construct(
        public string $incomingMessageId,
        public string $endpoint,
        public array $commands = [],
        public array $events = [],
    ) {}
}
