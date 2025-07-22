<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Context\MessageCollector;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Persistence\Outbox;
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
            handler: function (array $events) use ($envelopeFactory, $dispatcher): void {
                $outboxes = $this->storage->findOutboxes(
                    endpoint: $this->endpoint,
                    incomingMessageIds: array_column($events, 'messageId'),
                );

                if (\count($outboxes) < \count($events)) {
                    $outboxesById = array_column($outboxes, null, 'incomingMessageId');
                    $transaction = $this->storage->beginTransaction($this->endpoint);
                    $outboxesToRecord = [];

                    try {
                        foreach ($events as $event) {
                            if (isset($outboxesById[$event->messageId])) {
                                continue;
                            }

                            $messageCollector = new MessageCollector();
                            $this->listeners->handle($event, new Context(
                                endpoint: $this->endpoint,
                                envelope: $event,
                                transactionFactory: static fn(): object => $transaction->wrappedTransaction,
                                envelopeFactory: $envelopeFactory,
                                messageCollector: $messageCollector,
                                dispatcher: $dispatcher,
                            ));

                            $outboxesToRecord[] = $outboxes[] = new Outbox(
                                incomingMessageId: $event->messageId,
                                commands: $messageCollector->commands,
                                events: $messageCollector->events,
                            );
                        }

                        \assert($outboxesToRecord !== []);
                        $transaction->recordOutboxes($outboxesToRecord);

                        $transaction->commit();
                    } catch (\Throwable $exception) {
                        $transaction->rollback();

                        throw $exception;
                    }
                }

                $incomingMessageIdsToMarkSent = [];
                $commandsToSend = [];
                $eventsToPublish = [];

                foreach ($outboxes as $outbox) {
                    if (!$outbox->dispatched) {
                        $incomingMessageIdsToMarkSent[] = $outbox->incomingMessageId;
                        $commandsToSend = [...$commandsToSend, ...$outbox->commands];
                        $eventsToPublish = [...$eventsToPublish, ...$outbox->events];
                    }
                }

                if ($incomingMessageIdsToMarkSent === []) {
                    return;
                }

                if ($commandsToSend !== []) {
                    $dispatcher->send($commandsToSend);
                }

                if ($eventsToPublish !== []) {
                    $dispatcher->publish($eventsToPublish);
                }

                $this->storage->markOutboxesDispatched($this->endpoint, $incomingMessageIdsToMarkSent);
            },
            maxBatchSize: $this->maxBatchSize,
        );
    }
}
