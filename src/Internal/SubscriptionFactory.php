<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\SubscriberTransport;

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
        private string $transactionKey,
    ) {}

    /**
     * @param Router<non-negative-int> $eventRouter
     * @param list<SubscriberTransport> $transports
     * @return Subscription<TTransaction>
     */
    public function build(Wrapper $wrapper, Router $eventRouter, array $transports): Subscription
    {
        $transport = null;

        foreach ($this->listeners->eventClasses as $eventClass) {
            $matchedTransport = $transports[$eventRouter->route($eventClass)];

            if ($transport !== null && $transport !== $matchedTransport) {
                throw new \LogicException();
            }

            $transport = $matchedTransport;
        }

        \assert($transport !== null);

        return new Subscription(
            name: $this->name,
            listeners: $this->listeners,
            transport: $transport,
            storage: $this->storage,
            transactionKey: $this->transactionKey,
            wrapper: $wrapper,
        );
    }
}
