<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Thesis\MessageBus\Transport\Canceller;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;

final class InMemoryTransport implements CommandSender, CommandReceiver, EventPublisher
{
    /**
     * @var array<non-empty-string, Queue>
     */
    private array $commandQueues = [];

    public function send(string $endpoint, array $commands): void
    {
        ($this->commandQueues[$endpoint] ??= new Queue())->push($commands);
    }

    public function startCommandConsumer(string $endpoint, callable $handler): Canceller
    {
        return ($this->commandQueues[$endpoint] ??= new Queue())->startConsumer($handler);
    }

    /**
     * @var array<class-string, non-empty-list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, Queue>
     */
    private array $eventQueues = [];

    public function subscribe(string $subscription, array $toEventClasses): void
    {
        foreach ($toEventClasses as $eventClass) {
            $this->subscriptions[$eventClass][] = $subscription;
        }
    }

    public function publish(string $publisher, array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions[$event->messageClass] ?? [] as $publisher) {
                ($this->eventQueues[$publisher] ??= new Queue())->push([$event]);
            }
        }
    }

    public function startSubscription(string $subscription, callable $handler): Canceller
    {
        return ($this->eventQueues[$subscription] ??= new Queue())->startConsumer($handler);
    }
}
