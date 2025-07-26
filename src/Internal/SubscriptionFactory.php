<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\PublisherTransport;

/**
 * @internal
 * @template TTransaction of object
 */
final readonly class SubscriptionFactory
{
    /**
     * @param non-empty-string $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        private string $name,
        private EventListeners $listeners,
        private Storage $storage,
        private string $persistenceKey,
    ) {}

    /**
     * @param Router<non-negative-int> $eventRouter
     * @param list<PublisherTransport> $publisherTransports
     * @return Subscription<TTransaction>
     */
    public function build(Wrapper $wrapper, Router $eventRouter, array $publisherTransports): Subscription
    {
        $publisher = null;

        foreach ($this->listeners->eventClasses as $eventClass) {
            $matchedPublisher = $publisherTransports[$eventRouter->route($eventClass)];

            if ($publisher !== null && $publisher !== $matchedPublisher) {
                throw new \LogicException();
            }

            $publisher = $matchedPublisher;
        }

        \assert($publisher !== null);

        return new Subscription(
            name: $this->name,
            listeners: $this->listeners,
            publisher: $publisher,
            storage: $this->storage,
            persistenceKey: $this->persistenceKey,
            wrapper: $wrapper,
        );
    }
}
