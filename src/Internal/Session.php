<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Message;
use Thesis\MessageBus\Dispatching\DispatchContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\HandleContext;
use Thesis\MessageBus\Handling\Handler;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\Transaction;
use Thesis\MessageBus\Transport\Transport;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 * @template TTransaction of Transaction
 */
final class Session
{
    /**
     * @template TMethodTransaction of Transaction
     * @param non-empty-string $endpoint
     * @param Storage<TMethodTransaction> $storage
     * @param HandlerRegistry<TMethodTransaction> $syncHandlerRegistry
     */
    public static function dispatchFromEndpoint(
        string $endpoint,
        Storage $storage,
        Transport $transport,
        HandlerRegistry $syncHandlerRegistry,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        Envelope $envelope,
    ): void {
        $envelope = $outgoingEnvelopeProcessor->process($envelope, new DispatchContext($endpoint));

        $syncHandlers = $syncHandlerRegistry->getHandlers($envelope->messageClass);

        if ($syncHandlers === []) {
            $transport->publish([$envelope]);

            return;
        }

        $transaction = $storage->beginTransaction();

        try {
            $session = new self(
                endpoint: $endpoint,
                transaction: $transaction,
                outgoingEnvelopeProcessor: $outgoingEnvelopeProcessor,
                syncHandlerRegistry: $syncHandlerRegistry,
            );
            $session->handle($envelope, $syncHandlers);

            if ($envelope->isEvent) {
                $session->envelopesToPublish[] = $envelope;
            }

            $outbox = new Outbox(
                endpoint: $endpoint,
                messageId: $envelope->messageId,
                envelopes: $session->envelopesToPublish,
            );

            $transaction->commit($outbox);
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }

        if ($outbox->envelopes !== []) {
            $transport->publish($outbox->envelopes);
            $storage->updateOutbox($outbox->toEmpty());
        }
    }

    /**
     * @template TMethodTransaction of Transaction
     * @param non-empty-string $endpoint
     * @param Storage<TMethodTransaction> $storage
     * @param HandlerRegistry<TMethodTransaction> $asyncHandlerRegistry
     * @param HandlerRegistry<TMethodTransaction> $syncHandlerRegistry
     */
    public static function consume(
        string $endpoint,
        Storage $storage,
        Transport $transport,
        HandlerRegistry $asyncHandlerRegistry,
        HandlerRegistry $syncHandlerRegistry,
        OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        Envelope $envelope,
    ): void {
        $outbox = $storage->findOutbox(
            endpoint: $endpoint,
            messageId: $envelope->messageId,
        );

        if ($outbox === null) {
            $transaction = $storage->beginTransaction();

            try {
                $session = new self(
                    endpoint: $endpoint,
                    transaction: $transaction,
                    outgoingEnvelopeProcessor: $outgoingEnvelopeProcessor,
                    syncHandlerRegistry: $syncHandlerRegistry,
                );
                $session->handle($envelope, $asyncHandlerRegistry->getHandlers($envelope->messageClass));

                $outbox = new Outbox(
                    endpoint: $endpoint,
                    messageId: $envelope->messageId,
                    envelopes: $session->envelopesToPublish,
                );

                $transaction->commit($outbox);
            } catch (\Throwable $exception) {
                $transaction->rollback();

                throw $exception;
            }
        }

        if ($outbox->envelopes !== []) {
            $transport->publish($outbox->envelopes);
            $storage->updateOutbox($outbox->toEmpty());
        }
    }

    /**
     * @param non-empty-string $endpoint
     * @param TTransaction $transaction
     * @param HandlerRegistry<TTransaction> $syncHandlerRegistry
     */
    private function __construct(
        private readonly string $endpoint,
        private readonly Transaction $transaction,
        private readonly OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        private readonly HandlerRegistry $syncHandlerRegistry,
    ) {}

    /**
     * @var list<Envelope>
     */
    private array $envelopesToPublish = [];

    /**
     * @template TMessage of Message
     * @param Envelope<TMessage> $envelope
     * @param list<Handler<TMessage, TTransaction>> $handlers
     */
    private function handle(Envelope $envelope, array $handlers): void
    {
        if ($envelope->isCommand && \count($handlers) !== 1) {
            throw new \LogicException('Command must have 1 handler');
        }

        $context = new HandleContext(
            endpoint: $this->endpoint,
            transaction: $this->transaction,
        );

        foreach ($handlers as $handler) {
            $handler->handle($envelope, $context);
        }

        foreach ($context->dispatchedEnvelopes as $dispatchedEnvelope) {
            $this->dispatch($dispatchedEnvelope, $envelope);
        }
    }

    private function dispatch(Envelope $message, Envelope $cause): void
    {
        $envelope = $this->outgoingEnvelopeProcessor->process($message, new DispatchContext($this->endpoint, $cause));

        $syncHandlers = $this->syncHandlerRegistry->getHandlers($envelope->messageClass);

        if ($syncHandlers !== []) {
            $this->handle($envelope, $syncHandlers);

            if ($envelope->isCommand) {
                return;
            }
        }

        $this->envelopesToPublish[] = $envelope;
    }
}
