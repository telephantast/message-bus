<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Thesis\MessageBus\Transport\ConsumerTransport;
use Thesis\MessageBus\Transport\PublisherTransport;
use Thesis\MessageBus\Transport\Run;

final class InMemoryTransport implements ConsumerTransport, PublisherTransport
{
    /**
     * @var array<non-empty-string, Queue>
     */
    private array $queues = [];

    /**
     * @param positive-int $maxBatchSize
     */
    public function __construct(
        private readonly int $maxBatchSize = 1,
    ) {}

    public function send(string $queue, array $commands): void
    {
        ($this->queues[$queue] ??= new Queue())->push($commands);
    }

    public function runConsumer(string $queue, callable $consumer): Run
    {
        return ($this->queues[$queue] ??= new Queue())->run($consumer, $this->maxBatchSize);
    }

    /**
     * @var array<class-string, non-empty-list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, Queue>
     */
    private array $streams = [];

    public function subscribe(string $stream, array $eventClasses): void
    {
        foreach ($eventClasses as $eventClass) {
            $this->subscriptions[$eventClass][] = $stream;
        }
    }

    public function runSubscription(string $stream, array $eventClasses, callable $subscription): Run
    {
        return ($this->streams[$stream] ??= new Queue())->run($subscription, $this->maxBatchSize);
    }

    public function publish(array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions[$event->messageClass] ?? [] as $subscriptionName) {
                ($this->streams[$subscriptionName] ??= new Queue())->push([$event]);
            }
        }
    }
}
