<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
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
    /**
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        private string $name,
        private CommandHandlers $handlers,
        private CommandReceiver $receiver,
        private Storage $storage,
    ) {}

    public function setup(): void
    {
        $this->storage->setup();
    }

    public function start(EnvelopeFactory $envelopeFactory, CommandDispatcher $commandDispatcher, EventDispatcher $eventDispatcher): Run
    {
        $endpoint = Endpoint::queue($this->name);

        return $this->receiver->startQueue(
            queue: $this->name,
            handler: function (Envelope $command) use ($endpoint, $envelopeFactory, $commandDispatcher, $eventDispatcher): void {
                $outbox = $this->storage->findOutbox(
                    endpoint: $endpoint,
                    incomingMessageId: $command->messageId,
                );

                if ($outbox === null) {
                    $context = null;

                    $transaction = $this->storage->beginTransaction(
                        endpoint: $endpoint,
                        incomingMessageId: $command->messageId,
                    );

                    try {
                        $context = new CollectingContext(
                            endpoint: $endpoint,
                            transaction: $transaction->wrappedTransaction,
                            envelopeFactory: $envelopeFactory,
                            envelope: $command,
                        );

                        $this->handlers->handle($command, $context);

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
                    endpoint: $endpoint,
                    incomingMessageId: $command->messageId,
                );
            },
        );
    }
}
