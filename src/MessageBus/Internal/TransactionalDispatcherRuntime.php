<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Processing\Deduplicator;
use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\TransactionalDispatcher;
use const Thesis\MessageBus\MESSAGE_ID;

/**
 * @internal
 *
 * @template-covariant Tx of object
 */
final readonly class TransactionalDispatcherRuntime implements ImmediateMessageHandler, ConsumerHandler
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

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
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
                $txScope->ensureBegun();
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

                $txScope->rollbackIfActive();

                return;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes);
            }

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
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
                $txScope->ensureBegun();
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

                $txScope->rollbackIfActive();

                return Disposition::Ack;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes);
            }

            $txScope->commitIfBegun();

            return Disposition::Ack;
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }
    }
}
