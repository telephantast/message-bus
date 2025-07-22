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

    public function startQueue(string $queue, callable $handler, int $maxBatchSize = 1): Run
    {
        return ($this->commandQueues[$queue] ??= new Queue())->startConsumer($handler, $maxBatchSize);
    }

    /**
     * @var array<class-string, non-empty-list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, Queue>
     */
    private array $eventQueues = [];

    public function subscribe(string $subscription, array $eventClasses): void
    {
        foreach ($eventClasses as $eventClass) {
            $this->subscriptions[$eventClass][] = $subscription;
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

    public function startSubscription(string $subscription, callable $handler, int $maxBatchSize = 1): Run
    {
        return ($this->eventQueues[$subscription] ??= new Queue())->startConsumer($handler, $maxBatchSize);
    }
}
