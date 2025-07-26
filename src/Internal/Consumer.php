<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\ConsumerTransport;
use Thesis\MessageBus\Transport\Run;

/**
 * @internal
 * @template TTransaction of object
 */
final readonly class Consumer
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction, true> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        string $name,
        private CommandHandlers $handlers,
        private ConsumerTransport $receiver,
        private Storage $storage,
        private string $persistenceKey,
        private Wrapper $wrapper,
    ) {
        $this->endpoint = Endpoint::consumer($name);
    }

    public function setup(): void
    {
        $this->storage->setup();
    }

    public function run(Dispatcher $dispatcher): Run
    {
        return $this->receiver->runConsumer(
            queue: $this->endpoint->name,
            consumer: new AsyncHandler(
                endpoint: $this->endpoint,
                handler: $this->handlers,
                storage: $this->storage,
                persistenceKey: $this->persistenceKey,
                wrapper: $this->wrapper,
                dispatcher: $dispatcher,
            ),
        );
    }
}
