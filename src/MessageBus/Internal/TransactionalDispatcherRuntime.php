<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Log\LoggerInterface;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Processing\Deduplicator;
use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\TransactionalDispatcher;

/**
 * @internal
 *
 * @template-covariant Tx of object
 * @implements Runtime<Tx>
 */
final readonly class TransactionalDispatcherRuntime implements Runtime
{
    /**
     * @param TransactionalDispatcher<Tx> $dispatcher
     * @param TransactionScopeFactory<Tx> $transactionScopeFactory
     * @param Deduplicator<Tx> $deduplicator
     */
    public function __construct(
        private TransactionalDispatcher $dispatcher,
        private TransactionScopeFactory $transactionScopeFactory,
        private Deduplicator $deduplicator,
        private LoggerInterface $logger,
    ) {}

    public function dispatch(string $endpoint, array $envelopes): void
    {
        $this->dispatcher->dispatch($envelopes);
    }

    public function dispatchIdempotently(ProcessingId $id, array $envelopes): void
    {
        $txScope = $this->transactionScopeFactory->create();
        $txScope->begin();

        try {
            if (!$this->deduplicator->markHandledInTransaction($txScope->transaction, $id)) {
                $this->logger->debug('Dispatch already handled; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->rollbackIfActive();

                return;
            }

            $this->dispatcher->dispatchInTransaction($txScope->transaction, $envelopes);

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }
    }

    public function handle(string $endpoint, callable $handler): void
    {
        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $handler($txScope->transaction, $this->dispatcher);

            if ($outboundEnvelopes !== []) {
                if ($txScope->hasBegun) {
                    $this->dispatcher->dispatchInTransaction($txScope->transaction, $outboundEnvelopes);
                } else {
                    $this->dispatcher->dispatch($outboundEnvelopes);
                }
            }

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }
    }

    public function handleIdempotently(ProcessingId $id, callable $handler): void
    {
        if ($this->deduplicator->isHandled($id)) {
            $this->logger->debug('Message already handled; skipping.', [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ]);

            return;
        }

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $handler($txScope->transaction, $this->dispatcher);

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

    public function consumeIdempotently(ProcessingId $id, InboundEnvelope $envelope, callable $handler): void
    {
        $this->handleIdempotently($id, $handler);
    }
}
