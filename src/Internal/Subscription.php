<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\EventListeners;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\Canceller;
use Thesis\MessageBus\Transport\EventPublisher;

/**
 * @template TTransaction of object
 */
final readonly class Subscription
{
    /**
     * @param non-empty-string $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        private string $name,
        private EventListeners $listeners,
        private EventPublisher $publisher,
        private Storage $storage,
    ) {}

    public function setup(): void
    {
        $this->storage->setup();
        $this->publisher->subscribe($this->name, $this->listeners->eventClasses);
    }

    public function start(
        EnvelopeFactory $envelopeFactory,
        CommandDispatcher $commandDispatcher,
        EventDispatcher $eventDispatcher,
    ): Canceller {
        return $this->publisher->startSubscription(
            subscription: $this->name,
            handler: function (Envelope $command) use ($envelopeFactory, $commandDispatcher, $eventDispatcher): void {
                $outbox = $this->storage->findOutbox(
                    endpoint: $this->name,
                    incomingMessageId: $command->messageId,
                );

                if ($outbox === null) {
                    $context = null;

                    $transaction = $this->storage->beginTransaction(
                        endpoint: $this->name,
                        incomingMessageId: $command->messageId,
                    );

                    try {
                        $context = new CollectingContext(
                            endpoint: $this->name,
                            transaction: $transaction->wrappedTransaction,
                            envelopeFactory: $envelopeFactory,
                            envelope: $command,
                        );

                        $this->listeners->handle($command, $context);

                        $outbox = new Outbox($context->commands, $context->events);

                        $transaction->recordOutbox($outbox);
                        $transaction->commit();
                    } catch (\Throwable $exception) {
                        $transaction->rollback();

                        throw $exception;
                    } finally {
                        $context?->close();
                    }
                }

                if ($outbox->dispatched) {
                    return;
                }

                if ($outbox->commands !== []) {
                    $commandDispatcher->send($outbox->commands);
                }

                if ($outbox->events !== []) {
                    $eventDispatcher->publish($outbox->events);
                }

                $this->storage->markOutboxDispatched(
                    endpoint: $this->name,
                    incomingMessageId: $command->messageId,
                );
            },
        );
    }
}
