<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\EventPublisher;

final readonly class EventDispatcher
{
    /**
     * @param Router<non-negative-int> $router
     * @param list<EventPublisher> $publishers
     */
    public function __construct(
        private Router $router,
        private array $publishers,
    ) {}

    /**
     * @param non-empty-string $subscriptionName
     * @param non-empty-list<class-string> $toEventClasses
     */
    public function subscribe(string $subscriptionName, array $toEventClasses): void
    {
        $routedEventsByKey = [];

        foreach ($toEventClasses as $eventClass) {
            $routedEventsByKey[$this->router->route($eventClass)][] = $eventClass;
        }

        foreach ($routedEventsByKey as $key => $routedEvents) {
            $this->publishers[$key]->subscribe($subscriptionName, $routedEvents);
        }
    }

    /**
     * @param non-empty-list<Envelope> $events
     */
    public function publish(array $events): void
    {
        $routedEventsByKey = [];

        foreach ($events as $event) {
            $routedEventsByKey[$this->router->route($event->messageClass)][] = $event;
        }

        foreach ($routedEventsByKey as $key => $routedEvents) {
            $this->publishers[$key]->publish($routedEvents);
        }
    }
}
