<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\CommandHandlers;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\Canceller;
use Thesis\MessageBus\Transport\CommandReceiver;

/**
 * @template TTransaction of object
 */
final readonly class CommandEndpoint
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

    public function startConsumer(EnvelopeFactory $envelopeFactory, CommandDispatcher $commandDispatcher, EventDispatcher $eventDispatcher): Canceller
    {
        return $this->receiver->startCommandConsumer(
            endpoint: $this->name,
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
                    endpoint: $this->name,
                    incomingMessageId: $command->messageId,
                );
            },
        );
    }
}
