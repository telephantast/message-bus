<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\OutboxBuilder;
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
     */
    public function __construct(
        string $name,
        private EventListeners $listeners,
        private EventPublisher $publisher,
        private Storage $storage,
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
            handler: function (Envelope $command) use ($envelopeFactory, $dispatcher): void {
                $outbox = $this->storage->findOutbox(
                    endpoint: $this->endpoint,
                    incomingMessageId: $command->messageId,
                );

                if ($outbox === null) {
                    $transaction = $this->storage->beginTransaction(
                        endpoint: $this->endpoint,
                        incomingMessageId: $command->messageId,
                    );

                    try {
                        $outboxBuilder = new OutboxBuilder();
                        $context = new Context(
                            endpoint: $this->endpoint,
                            envelope: $command,
                            transactionFactory: static fn(): object => $transaction->wrappedTransaction,
                            envelopeFactory: $envelopeFactory,
                            outboxBuilder: $outboxBuilder,
                            dispatcher: $dispatcher,
                        );

                        $this->listeners->handle($command, $context);

                        $outbox = $outboxBuilder->build();

                        $transaction->recordOutbox($outbox);
                        $transaction->commit();
                    } catch (\Throwable $exception) {
                        $transaction->rollback();

                        throw $exception;
                    }
                }

                if ($outbox->dispatched) {
                    return;
                }

                if ($outbox->commands !== []) {
                    $dispatcher->send($outbox->commands);
                }

                if ($outbox->events !== []) {
                    $dispatcher->publish($outbox->events);
                }

                $this->storage->markOutboxDispatched(
                    endpoint: $this->endpoint,
                    incomingMessageId: $command->messageId,
                );
            },
        );
    }
}
