<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\PublisherTransport;
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
     */
    public function __construct(
        string $name,
        private EventListeners $listeners,
        private PublisherTransport $publisher,
        private Storage $storage,
    ) {
        $this->endpoint = Endpoint::subscription($name);
    }

    public function setup(): void
    {
        $this->storage->setup();

        if ($this->listeners->eventClasses !== []) {
            $this->publisher->subscribe($this->endpoint->name, $this->listeners->eventClasses);
        }
    }

    public function run(EnvelopeFactory $envelopeFactory, Dispatcher $dispatcher): Run
    {
        return $this->publisher->runSubscription(
            stream: $this->endpoint->name,
            subscription: new AsyncHandler(
                endpoint: $this->endpoint,
                handler: $this->listeners,
                storage: $this->storage,
                envelopeFactory: $envelopeFactory,
                dispatcher: $dispatcher,
            ),
        );
    }
}
