<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Envelope;

final class OutboxBuilder
{
    /**
     * @var list<Envelope>
     */
    private array $commands = [];

    /**
     * @param list<Envelope> $commands
     */
    public function addCommands(array $commands): void
    {
        $this->commands = [...$this->commands, ...$commands];
    }

    /**
     * @var list<Envelope>
     */
    private array $events = [];

    /**
     * @param list<Envelope> $events
     */
    public function addEvents(array $events): void
    {
        $this->events = [...$this->events, ...$events];
    }

    public function build(): Outbox
    {
        return new Outbox(
            commands: $this->commands,
            events: $this->events,
        );
    }
}
