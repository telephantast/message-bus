<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

interface EventPublisher
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function subscribe(string $endpoint, array $toEvents): void;

    /**
     * @param non-empty-string $atEndpoint
     * @param non-empty-list<Envelope<Event>> $events
     */
    public function publish(string $atEndpoint, array $events): void;
}
