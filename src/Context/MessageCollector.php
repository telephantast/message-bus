<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Context;

use Thesis\MessageBus\Envelope;

final class MessageCollector
{
    /**
     * @var list<Envelope>
     */
    public private(set) array $commands = [];

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
    public private(set) array $events = [];

    /**
     * @param list<Envelope> $events
     */
    public function addEvents(array $events): void
    {
        $this->events = [...$this->events, ...$events];
    }
}
