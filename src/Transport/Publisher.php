<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

interface Publisher
{
    /**
     * @param non-empty-list<Envelope<Event>> $events
     */
    public function publish(array $events): void;
}
