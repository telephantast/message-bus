<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Persistence\Connection;
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
final readonly class TransactionalRuntime implements ImmediateMessageHandler, ConsumerHandler
{
    /**
     * @param non-empty-string $endpoint
     * @param TransactionalDispatcher<Tx> $dispatcher
     * @param Connection<Tx> $connection
     * @param Deduplicator<Tx> $deduplicator
     * @param HandlerExecutor<Tx> $handlerExecutor
     */
    public function __construct(
        private string $endpoint,
        private HandlerExecutor $handlerExecutor,
        private InboundMessageFactory $inboundMessageFactory,
        private TransactionalDispatcher $dispatcher,
        private Connection $connection,
        private Deduplicator $deduplicator,
        private LoggerInterface $logger,
    ) {}

    public function handleImmediately(object $message, Headers $headers): void
    {
        if ($headers->has(MESSAGE_ID)) {
            $this->handleMessageIdempotently($message, $headers);

            return;
        }

        $txScope = new RuntimeTransactionScope($this->connection);

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $headers,
                txScope: $txScope,
            );

            if ($outboundEnvelopes !== []) {
                match ($txScope->hasBegun) {
                    true => $this->dispatcher->dispatchInTransaction($txScope->handle, $outboundEnvelopes),
                    false => $this->dispatcher->dispatch($outboundEnvelopes),
                };
            }

            $txScope->commit();
        } catch (\Throwable $exception) {
            $txScope->close();

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

        $txScope = new RuntimeTransactionScope($this->connection);

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $headers,
                txScope: $txScope,
            );

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            $marked = match ($txScope->hasBegun) {
                true => $this->deduplicator->markHandledInTransaction($txScope->handle, $id),
                false => $this->deduplicator->markHandled($id),
            };

            if (!$marked) {
                $this->logger->debug('Message was handled concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->close();

                return;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->handle, $outboundEnvelopes);
            }

            $txScope->commit();
        } catch (\Throwable $exception) {
            $txScope->close();

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

        $txScope = new RuntimeTransactionScope($this->connection);

        try {
            $outboundEnvelopes = $this->handlerExecutor->execute(
                message: $message,
                headers: $envelope->headers,
                txScope: $txScope,
            );

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            $marked = match ($txScope->hasBegun) {
                true => $this->deduplicator->markHandledInTransaction($txScope->handle, $id),
                false => $this->deduplicator->markHandled($id),
            };

            if (!$marked) {
                $this->logger->debug('Message was handled concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->close();

                return Disposition::Ack;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatcher->dispatchInTransaction($txScope->handle, $outboundEnvelopes);
            }

            $txScope->commit();

            return Disposition::Ack;
        } catch (\Throwable $exception) {
            $txScope->close();

            throw $exception;
        }
    }
}
