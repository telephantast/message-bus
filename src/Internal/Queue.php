<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Context\MessageCollector;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Persistence\Outbox;
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
            handler: function (array $commands) use ($envelopeFactory, $dispatcher): void {
                $outboxes = $this->storage->findOutboxes(
                    endpoint: $this->endpoint,
                    incomingMessageIds: array_column($commands, 'messageId'),
                );

                if (\count($outboxes) < \count($commands)) {
                    $outboxesById = array_column($outboxes, null, 'incomingMessageId');
                    $transaction = $this->storage->beginTransaction($this->endpoint);
                    $outboxesToRecord = [];

                    try {
                        foreach ($commands as $command) {
                            if (isset($outboxesById[$command->messageId])) {
                                continue;
                            }

                            $messageCollector = new MessageCollector();
                            $this->handlers->handle($command, new Context(
                                endpoint: $this->endpoint,
                                envelope: $command,
                                transactionFactory: static fn(): object => $transaction->wrappedTransaction,
                                envelopeFactory: $envelopeFactory,
                                messageCollector: $messageCollector,
                                dispatcher: $dispatcher,
                            ));

                            $outboxesToRecord[] = $outboxes[] = new Outbox(
                                incomingMessageId: $command->messageId,
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
