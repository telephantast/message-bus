<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\Transaction;
use Thesis\MessageBus\Transport\EventPublisher;

/**
 * @internal
 */
final class RootContext extends Context
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param non-empty-string $endpoint
     * @param Handler<TMessage> $handler
     * @param Envelope<TMessage> $envelope
     * @return TResult
     */
    public static function handle(
        string $endpoint,
        Storage $storage,
        Dispatcher $dispatcher,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        EventPublisher $eventPublisher,
        Handler $handler,
        Envelope $envelope,
    ): mixed {
        $outbox = $storage->findOutbox(
            endpoint: $endpoint,
            incomingMessageId: $envelope->messageId,
        );

        if ($outbox === null) {
            $context = new self(
                endpoint: $endpoint,
                incomingMessageId: $envelope->messageId,
                storage: $storage,
                dispatcher: $dispatcher,
                outgoingEnvelopeProcessor: $outgoingEnvelopeProcessor,
                envelope: $envelope,
            );

            try {
                $outbox = new Outbox(
                    result: $handler->handle($envelope, $context),
                    commands: $context->commands,
                    events: $context->events,
                );

                if ($outbox->commands !== [] || $outbox->events !== []) {
                    $context->beginTransaction()->insertOutbox($outbox);
                }

                $context->storageTransaction?->commit();
            } catch (\Throwable $exception) {
                $context->storageTransaction?->rollback();

                throw $exception;
            } finally {
                $context->close();
            }
        }

        if ($outbox->commands !== []) {
            $dispatcher->dispatchCommands($outbox->commands);
        }

        if ($outbox->events !== []) {
            $eventPublisher->publish($endpoint, $outbox->events);
        }

        $storage->markOutboxSent($endpoint, $envelope->messageId);

        return $outbox->result;
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     * @param Envelope<*> $envelope
     */
    private function __construct(
        public readonly string $endpoint,
        private readonly string $incomingMessageId,
        private readonly Storage $storage,
        private readonly Dispatcher $dispatcher,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        Envelope $envelope,
    ) {
        parent::__construct($outgoingEnvelopeProcessor, $envelope);
    }

    private ?Transaction $storageTransaction = null;

    public object $transaction {
        get => $this->beginTransaction()->wrappedTransaction;
    }

    private function beginTransaction(): Transaction
    {
        return $this->storageTransaction ??= $this->storage->beginTransaction(
            endpoint: $this->endpoint,
            incomingMessageId: $this->incomingMessageId,
        );
    }

    /**
     * @var list<Envelope<Command>>
     */
    private array $commands = [];

    protected function doSend(array $commands): void
    {
        $this->commands = [...$this->commands, ...$commands];
    }

    /**
     * @var list<Envelope<Event>>
     */
    private array $events = [];

    protected function doPublish(array $events): void
    {
        $this->events = [...$this->events, ...$events];
    }

    protected function doInvoke(Envelope $call, Context $parentContext): mixed
    {
        return $this->dispatcher->dispatchCall($call, $parentContext);
    }

    private function close(): void
    {
        $this->storageTransaction = null;
        $this->events = [];
        $this->commands = [];

        // todo bool closed
    }
}
