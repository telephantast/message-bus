<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Persistence\DispatchOutbox;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\Transaction;
use Thesis\MessageBus\Transport\EventPublisher;
use function Amp\delay;

/**
 * @internal
 */
final class RootContext extends Context
{
    /**
     * @param non-empty-string $endpoint
     * @param Handler<*> $handler
     * @param Envelope<Command|Event> $envelope
     */
    public static function handleCommandOrEvent(
        string $endpoint,
        Storage $storage,
        Dispatcher $dispatcher,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        EventPublisher $eventPublisher,
        Handler $handler,
        Envelope $envelope,
    ): void {
        if ($envelope->message instanceof DispatchOutbox) {
            self::handleDispatchOutbox(
                endpoint: $endpoint,
                incomingMessageId: $envelope->message->incomingMessageId,
                storage: $storage,
                dispatcher: $dispatcher,
                eventPublisher: $eventPublisher,
            );

            return;
        }

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

            $transaction = $context->beginTransaction();

            try {
                /** @phpstan-ignore argument.type */
                $handler->handle($envelope, $context);

                $outbox = new Outbox($context->commands, $context->events);

                $transaction->insertOutbox($outbox);
                $transaction->commit();
            } catch (\Throwable $exception) {
                $transaction->rollback();

                throw $exception;
            } finally {
                $context->close();
            }
        }

        if ($outbox->dispatched) {
            return;
        }

        if ($outbox->commands !== []) {
            $dispatcher->dispatchCommands($outbox->commands);
        }

        if ($outbox->events !== []) {
            $eventPublisher->publish($endpoint, $outbox->events);
        }

        $storage->markOutboxDispatched($endpoint, $envelope->messageId);
    }

    /**
     * @template TResult
     * @param Handler<*> $handler
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    public static function handleCall(
        Endpoint $endpoint,
        Storage $storage,
        Dispatcher $dispatcher,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        EventPublisher $eventPublisher,
        Handler $handler,
        Envelope $call,
    ): mixed {
        $context = new self(
            endpoint: $endpoint->name,
            incomingMessageId: $call->messageId,
            storage: $storage,
            dispatcher: $dispatcher,
            outgoingEnvelopeProcessor: $outgoingEnvelopeProcessor,
            envelope: $call,
        );

        try {
            /** @phpstan-ignore argument.type */
            $result = $handler->handle($call, $context);
            $commands = $context->commands;
            $events = $context->events;
            $useOutbox = $commands !== [] || $events !== [];

            if ($useOutbox) {
                $context->beginTransaction()->insertOutbox(new Outbox($commands, $events));

                // todo Sender::sendTo()?
                $endpoint->send([$context->prepareOutgoingEnvelope(new DispatchOutbox($call->messageId))]);
            }

            $context->storageTransaction?->commit();
        } catch (\Throwable $exception) {
            $context->storageTransaction?->rollback();

            throw $exception;
        } finally {
            $context->close();
        }

        if ($useOutbox) {
            try {
                if ($commands !== []) {
                    $dispatcher->dispatchCommands($commands);
                }

                if ($events !== []) {
                    $eventPublisher->publish($endpoint->name, $events);
                }

                $storage->markOutboxDispatched($endpoint->name, $call->messageId);
            } catch (\Throwable $exception) {
                // todo log
            }
        }

        return $result;
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $incomingMessageId
     */
    private static function handleDispatchOutbox(
        string $endpoint,
        string $incomingMessageId,
        Storage $storage,
        Dispatcher $dispatcher,
        EventPublisher $eventPublisher,
    ): void {
        while (null === $outbox = $storage->findOutbox($endpoint, $incomingMessageId)) {
            delay(1);
        }

        if ($outbox->dispatched) {
            return;
        }

        if ($outbox->commands !== []) {
            $dispatcher->dispatchCommands($outbox->commands);
        }

        if ($outbox->events !== []) {
            $eventPublisher->publish($endpoint, $outbox->events);
        }

        $storage->markOutboxDispatched($endpoint, $incomingMessageId);
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
