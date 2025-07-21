<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;

final readonly class Dispatcher
{
    /**
     * @param Router<non-empty-string> $commandRouter
     * @param array<non-empty-string, CommandSender> $senders
     * @param Router<non-negative-int> $eventRouter
     * @param list<EventPublisher> $publishers
     * @param Router<non-empty-string> $callRouter
     * @param array<non-empty-string, Service<*>> $services
     */
    public function __construct(
        private Router $commandRouter,
        private array $senders,
        private Router $eventRouter,
        private array $publishers,
        private Router $callRouter,
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
            $this->senders[$queue]->send($queue, $routedCommands);
        }
    }

    /**
     * @template TResult
     * @param ?Context<*> $parentContext
     * @return ($call is Envelope<Call<TResult>> ? TResult : mixed)
     */
    public function invoke(Envelope $call, EnvelopeFactory $envelopeFactory, ?Context $parentContext = null): mixed
    {
        return $this->services[$this->callRouter->route($call->messageClass)]->invoke($call, $parentContext, $envelopeFactory, $this);
    }
}
