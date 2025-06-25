<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Event;

interface Subscriber
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $events
     */
    public function subscribe(string $endpoint, array $events): void;
}
