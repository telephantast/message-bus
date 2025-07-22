<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\Run;

/**
 * @template TTransaction of object
 */
final readonly class Subscription
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     * @param positive-int $maxBatchSize
     */
    public function __construct(
        string $name,
        private EventListeners $listeners,
        private EventPublisher $publisher,
        private Storage $storage,
        private int $maxBatchSize,
    ) {
        $this->endpoint = Endpoint::subscription($name);
    }

    public function setup(): void
    {
        $this->storage->setup();
        $this->publisher->subscribe($this->endpoint->name, $this->listeners->eventClasses);
    }

    public function start(EnvelopeFactory $envelopeFactory, Dispatcher $dispatcher): Run
    {
        return $this->publisher->startSubscription(
            subscription: $this->endpoint->name,
            consumer: new Consumer(
                endpoint: $this->endpoint,
                storage: $this->storage,
                handler: $this->listeners->handle(...),
                envelopeFactory: $envelopeFactory,
                dispatcher: $dispatcher,
            ),
            maxBatchSize: $this->maxBatchSize,
        );
    }
}
