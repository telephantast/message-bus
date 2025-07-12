<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;

final class CollectingPublisher implements Publisher
{
    /**
     * @var list<Envelope<Event>>
     */
    public private(set) array $events = [];

    public function publish(Event|Envelope ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->events = [
            ...$this->events,
            ...array_map(Envelope::wrap(...), $events),
        ];
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
