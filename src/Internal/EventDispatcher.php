<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\EventPublisher;

final readonly class EventDispatcher
{
    /**
     * @param array<non-empty-string, EventPublisher> $publishers
     */
    public function __construct(
        private Router $router,
        private array $publishers,
    ) {}

    /**
     * @param class-string $eventClass
     * @return non-empty-string
     */
    public function route(string $eventClass): string
    {
        return $this->router->route($eventClass);
    }

    /**
     * @param non-empty-string $subscription
     * @param non-empty-list<class-string> $toEventClasses
     */
    public function subscribe(string $subscription, array $toEventClasses): void
    {
        $routedEventsByEndpoint = [];

        foreach ($toEventClasses as $eventClass) {
            $routedEventsByEndpoint[$this->router->route($eventClass)][] = $eventClass;
        }

        foreach ($routedEventsByEndpoint as $endpoint => $routedEvents) {
            $this->publishers[$endpoint]->subscribe($subscription, $routedEvents);
        }
    }

    /**
     * @param non-empty-list<Envelope> $events
     */
    public function publish(array $events): void
    {
        $routedEventsByPublisher = [];

        foreach ($events as $event) {
            $routedEventsByPublisher[$this->router->route($event->messageClass)][] = $event;
        }

        foreach ($routedEventsByPublisher as $publisher => $routedEvents) {
            $this->publishers[$publisher]->publish($publisher, $routedEvents);
        }
    }
}
