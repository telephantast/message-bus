<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\Run;

final class InMemoryTransport implements CommandSender, CommandReceiver, EventPublisher
{
    /**
     * @var array<non-empty-string, Queue>
     */
    private array $commandQueues = [];

    public function send(string $queue, array $commands): void
    {
        ($this->commandQueues[$queue] ??= new Queue())->push($commands);
    }

    public function startQueue(string $queue, callable $handler): Run
    {
        return ($this->commandQueues[$queue] ??= new Queue())->startConsumer($handler);
    }

    /**
     * @var array<class-string, non-empty-list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, Queue>
     */
    private array $eventQueues = [];

    public function subscribe(string $subscriptionName, array $toEventClasses): void
    {
        foreach ($toEventClasses as $eventClass) {
            $this->subscriptions[$eventClass][] = $subscriptionName;
        }
    }

    public function publish(array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions[$event->messageClass] ?? [] as $subscriptionName) {
                ($this->eventQueues[$subscriptionName] ??= new Queue())->push([$event]);
            }
        }
    }

    public function startSubscription(string $subscriptionName, callable $handler): Run
    {
        return ($this->eventQueues[$subscriptionName] ??= new Queue())->startConsumer($handler);
    }
}
