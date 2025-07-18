<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Outbox
{
    public bool $dispatched;

    /**
     * @param list<Envelope> $commands
     * @param list<Envelope> $events
     */
    public function __construct(
        public array $commands,
        public array $events,
        bool $dispatched = false,
    ) {
        $this->dispatched = $dispatched || ($commands === [] && $events === []);
    }

    public function toDispatched(): self
    {
        return new self(
            commands: $this->commands,
            events: $this->events,
            dispatched: true,
        );
    }
}
