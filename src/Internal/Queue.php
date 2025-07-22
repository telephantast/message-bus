<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\Run;

/**
 * @template TTransaction of object
 */
final readonly class Queue
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     * @param positive-int $maxBatchSize
     */
    public function __construct(
        string $name,
        private CommandHandlers $handlers,
        private CommandReceiver $receiver,
        private Storage $storage,
        private int $maxBatchSize,
    ) {
        $this->endpoint = Endpoint::queue($name);
    }

    public function setup(): void
    {
        $this->storage->setup();
    }

    public function start(EnvelopeFactory $envelopeFactory, Dispatcher $dispatcher): Run
    {
        return $this->receiver->startQueue(
            queue: $this->endpoint->name,
            consumer: new Consumer(
                endpoint: $this->endpoint,
                storage: $this->storage,
                handler: $this->handlers->handle(...),
                envelopeFactory: $envelopeFactory,
                dispatcher: $dispatcher,
            ),
            maxBatchSize: $this->maxBatchSize,
        );
    }
}
