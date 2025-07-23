<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\ProducerTransport;
use Thesis\MessageBus\Transport\PublisherTransport;

/**
 * @implements ContextInvoke<object>
 */
final readonly class Dispatcher implements ContextInvoke
{
    /**
     * @param Router<non-empty-string> $commandRouter
     * @param array<non-empty-string, ProducerTransport> $producers
     * @param Router<non-negative-int> $eventRouter
     * @param list<PublisherTransport> $publishers
     * @param Router<non-empty-string> $methodRouter
     * @param array<non-empty-string, Service<*>> $services
     */
    public function __construct(
        private Router $commandRouter,
        private array $producers,
        private Router $eventRouter,
        private array $publishers,
        private Router $methodRouter,
        private array $services,
    ) {}

    /**
     * @param non-empty-string $subscription
     * @param non-empty-list<class-string> $eventClasses
     */
    public function subscribe(string $subscription, array $eventClasses): void
    {
        $routedEventsByKey = [];

        foreach ($eventClasses as $eventClass) {
            $routedEventsByKey[$this->eventRouter->route($eventClass)][] = $eventClass;
        }

        foreach ($routedEventsByKey as $key => $routedEvents) {
            $this->publishers[$key]->subscribe($subscription, $routedEvents);
        }
    }

    /**
     * @param non-empty-list<Envelope> $events
     */
    public function publish(array $events): void
    {
        $routedEventsByKey = [];

        foreach ($events as $event) {
            $routedEventsByKey[$this->eventRouter->route($event->messageClass)][] = $event;
        }

        foreach ($routedEventsByKey as $key => $routedEvents) {
            $this->publishers[$key]->publish($routedEvents);
        }
    }

    /**
     * @param non-empty-list<Envelope> $commands
     */
    public function send(array $commands): void
    {
        $routedCommandsByQueue = [];

        foreach ($commands as $command) {
            $routedCommandsByQueue[$this->commandRouter->route($command->messageClass)][] = $command;
        }

        foreach ($routedCommandsByQueue as $queue => $routedCommands) {
            $this->producers[$queue]->send($queue, $routedCommands);
        }
    }

    public function invoke(Envelope $method, ?Context $parentContext = null): mixed
    {
        return $this->services[$this->methodRouter->route($method->messageClass)]->invoke($this, $method, $parentContext);
    }
}
