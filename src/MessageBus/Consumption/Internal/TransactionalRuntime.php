<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Consumption\Deduplicator;
use Thesis\MessageBus\Consumption\ProcessingId;
use Thesis\MessageBus\Handling\Internal\HandlerExecutor;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\TransactionalDispatcher;
use const Thesis\MessageBus\Protocol\MESSAGE_ID;

/**
 * @internal
 *
 * @template-covariant Tx of object
 */
final readonly class TransactionalRuntime implements ImmediateMessageHandler, ConsumerHandler
{
    /**
     * @param non-empty-string $endpoint
     * @param TransactionalDispatcher<Tx> $dispatcher
     * @param TransactionScopeFactory<Tx> $transactionScopeFactory
     * @param Deduplicator<Tx> $deduplicator
     * @param HandlerExecutor<Tx> $handlerExecutor
     */
    public function __construct(
        private string $endpoint,
        private HandlerExecutor $handlerExecutor,
        private InboundMessageFactory $inboundMessageFactory,
        private TransactionalDispatcher $dispatcher,
        private TransactionScopeFactory $transactionScopeFactory,
        private Deduplicator $deduplicator,
        private LoggerInterface $logger,
    ) {}

    public function handleImmediately(object $message, Headers $headers): void
    {
        if ($headers->has(MESSAGE_ID)) {
            $this->handleMessageIdempotently($message, $headers);

            return;
        }

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $headers,
                transaction: $txScope->transaction,
            );

            if ($outboundEnvelopes !== []) {
                match ($txScope->hasBegun) {
                    true => $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes),
                    false => $this->dispatcher->dispatch($outboundEnvelopes),
                };
            }

            $txScope->commit();
        } finally {
            $txScope->rollback();
        }
    }

    private function handleMessageIdempotently(object $message, Headers $headers): void
    {
        $id = new ProcessingId(
            endpoint: $this->endpoint,
            messageId: $headers->get(MESSAGE_ID),
        );

        if ($this->deduplicator->isHandled($id)) {
            $this->logger->debug('Message already handled; skipping.', [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ]);

            return;
        }

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $headers,
                transaction: $txScope->transaction,
            );

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            $marked = match ($txScope->hasBegun) {
                true => $this->deduplicator->markHandledInTransaction($txScope->transaction, $id),
                false => $this->deduplicator->markHandled($id),
            };

            if (!$marked) {
                $this->logger->debug('Message was handled concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                return;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes);
            }

            $txScope->commit();
        } finally {
            $txScope->rollback();
        }
    }

    public function handle(InboundEnvelope $envelope): Disposition
    {
        $id = new ProcessingId(
            endpoint: $this->endpoint,
            messageId: $envelope->headers->get(MESSAGE_ID),
        );

        if ($this->deduplicator->isHandled($id)) {
            $this->logger->debug('Message already handled; skipping.', [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ]);

            return Disposition::Ack;
        }

        $message = $this->inboundMessageFactory->build($envelope);

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $envelope->headers,
                transaction: $txScope->transaction,
            );

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            $marked = match ($txScope->hasBegun) {
                true => $this->deduplicator->markHandledInTransaction($txScope->transaction, $id),
                false => $this->deduplicator->markHandled($id),
            };

            if (!$marked) {
                $this->logger->debug('Message was handled concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                return Disposition::Ack;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes);
            }

            $txScope->commit();

            return Disposition::Ack;
        } finally {
            $txScope->rollback();
        }
    }
}
