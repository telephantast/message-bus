<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;

interface Publisher
{
    /**
     * @no-named-arguments
     * @param Event|Envelope<Event> ...$events
     */
    public function publish(Event|Envelope ...$events): void;
}
