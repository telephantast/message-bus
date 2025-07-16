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
        $context = new self(
            endpoint: $endpoint,
            storage: $storage,
            dispatcher: $dispatcher,
            outgoingEnvelopeProcessor: $outgoingEnvelopeProcessor,
            envelope: $envelope,
        );

        try {
            $result = $handler->handle($envelope, $context);

            if ($context->commands !== [] || $context->events !== []) {
                $context->beginTransaction()->insertOutbox(
                    new Outbox(
                        endpoint: $endpoint,
                        incomingMessageId: $envelope->messageId,
                        commands: $context->commands,
                        events: $context->events,
                    ),
                );
            }

            $context->storageTransaction?->commit();
            $commands = $context->commands;
            $events = $context->events;
        } catch (\Throwable $exception) {
            $context->storageTransaction?->rollback();

            throw $exception;
        } finally {
            $context->close();
        }

        if ($commands !== []) {
            $dispatcher->dispatchCommands($commands);
        }

        if ($events !== []) {
            $eventPublisher->publish($endpoint, $events);
        }

        return $result;
    }

    /**
     * @param non-empty-string $endpoint
     * @param Envelope<*> $envelope
     */
    private function __construct(
        public readonly string $endpoint,
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
        return $this->storageTransaction ??= $this->storage->beginTransaction();
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
