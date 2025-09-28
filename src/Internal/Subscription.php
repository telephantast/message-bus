<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\NullRun;
use Thesis\MessageBus\Transport\Run;
use Thesis\MessageBus\Transport\SubscriberTransport;

/**
 * @internal
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
        private SubscriberTransport $transport,
        private Storage $storage,
        private string $transactionKey,
        private Wrapper $wrapper,
    ) {
        $this->endpoint = Endpoint::subscription($name);
    }

    public function setup(): void
    {
        $this->storage->setup();

        if ($this->listeners->eventClasses !== []) {
            $this->transport->subscribe($this->endpoint->name, $this->listeners->eventClasses);
        }
    }

    public function run(Dispatcher $dispatcher): Run
    {
        if ($this->listeners->eventClasses === []) {
            return NullRun::Instance;
        }

        return $this->transport->runSubscription(
            stream: $this->endpoint->name,
            subscription: new AsyncHandler(
                endpoint: $this->endpoint,
                handler: $this->listeners,
                storage: $this->storage,
                transactionKey: $this->transactionKey,
                wrapper: $this->wrapper,
                dispatcher: $dispatcher,
            ),
        );
    }
}
