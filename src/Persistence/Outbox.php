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
     * @param list<Envelope<Command>> $commands
     * @param list<Envelope<Event>> $events
     */
    public function __construct(
        public mixed $result,
        public array $commands,
        public array $events,
    ) {}
}
