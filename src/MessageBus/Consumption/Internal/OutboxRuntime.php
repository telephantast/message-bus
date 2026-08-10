<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Consumption\Outbox;
use Thesis\MessageBus\Consumption\OutboxStorage;
use Thesis\MessageBus\Handling\Internal\HandlerExecutor;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Persistence\TransactionScope;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Protocol\DeserializationFailed;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\Protocol\CAUSE_ID;
use const Thesis\MessageBus\Protocol\CONTENT_TYPE;
use const Thesis\MessageBus\Protocol\CONVERSATION_ID;
use const Thesis\MessageBus\Protocol\CREATED_AT;
use const Thesis\MessageBus\Protocol\EXPIRES_AT;
use const Thesis\MessageBus\Protocol\MESSAGE_ID;
use const Thesis\MessageBus\Protocol\MESSAGE_TYPE;
use const Thesis\MessageBus\Protocol\ORIGIN_ENDPOINT;

/**
 * The trigger is intentionally dispatched before the transaction is committed:
 * - if this dispatch fails, the handling transaction is rolled back and there is
 *   no durable outbox work to lose.
 * - if the trigger is sent but the transaction fails to commit,
 *   the trigger will not find a record and will eventually die
 *   by the endpoint's trigger TTL check.
 * - if the transaction commits, the trigger wakes a consumer up to dispatch
 *   the durable outbox record even if this process dies before doing it directly.
 *
 * @internal
 *
 * @template-covariant Tx of object
 */
final readonly class OutboxRuntime implements ImmediateMessageHandler, ConsumerHandler
{
    private const string TRIGGER_TYPE = 'thesis.service.dispatch_outbox';

    private TimeSpan $triggerTtl;

    private TimeSpan $triggerRetryInterval;

    /**
     * @param non-empty-string $endpoint
     * @param TransactionScopeFactory<Tx> $transactionScopeFactory
     * @param OutboxStorage<Tx> $outboxStorage
     * @param HandlerExecutor<Tx> $handlerExecutor
     */
    public function __construct(
        private string $endpoint,
        private HandlerExecutor $handlerExecutor,
        private InboundMessageFactory $inboundMessageFactory,
        private Dispatcher $dispatcher,
        private TransactionScopeFactory $transactionScopeFactory,
        private OutboxStorage $outboxStorage,
        private LoggerInterface $logger,
        private IdGenerator $idGenerator,
        private ClockInterface $clock,
        ?TimeSpan $triggerTtl,
        ?TimeSpan $triggerRetryInterval,
    ) {
        $this->triggerTtl = $triggerTtl ?? TimeSpan::fromMinutes(30);
        $this->triggerRetryInterval = $triggerRetryInterval ?? TimeSpan::fromSeconds(10);
    }

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
                $messageId = $this->idGenerator->generateId();

                // because failed trigger dispatch should rollback storeOutbox()
                $txScope->begin();

                if (!$this->storeOutbox($txScope, $messageId, new Outbox($outboundEnvelopes))) {
                    throw new \LogicException('Outbox record could not be stored for a random message id.');
                }

                $this->dispatchTrigger($messageId);
            }

            $txScope->commit();
        } finally {
            $txScope->rollback();
        }
    }

    private function handleMessageIdempotently(object $message, Headers $headers): void
    {
        $messageId = $headers->get(MESSAGE_ID);

        $outbox = $this->outboxStorage->find($this->endpoint, $messageId);

        if ($outbox !== null) {
            $this->logger->debug('Outbox already exists; skipping.', [
                'endpoint' => $this->endpoint,
                'message_id' => $messageId,
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

            $outbox = new Outbox($outboundEnvelopes);

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            if (!$this->storeOutbox($txScope, $messageId, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $this->endpoint,
                    'message_id' => $messageId,
                ]);

                return;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatchTrigger($messageId);
            }

            $txScope->commit();
        } finally {
            $txScope->rollback();
        }
    }

    /**
     * @param non-empty-string $messageId
     */
    private function dispatchTrigger(string $messageId): void
    {
        $now = $this->clock->now();

        $this->dispatcher->dispatch([
            new OutboundEnvelope(
                operation: Operation::Send,
                address: $this->endpoint,
                payload: json_encode(['message_id' => $messageId], JSON_THROW_ON_ERROR),
                headers: new Headers()
                    ->with(CONTENT_TYPE, 'application/json')
                    ->with(MESSAGE_TYPE, self::TRIGGER_TYPE)
                    ->with(ORIGIN_ENDPOINT, $this->endpoint)
                    ->with(MESSAGE_ID, $this->idGenerator->generateId())
                    ->with(CAUSE_ID, $messageId)
                    ->with(CONVERSATION_ID, $messageId)
                    ->with(CREATED_AT, $now)
                    ->with(EXPIRES_AT, $now->modify("{$this->triggerTtl->toMicroseconds()} microseconds")),
            ),
        ]);
    }

    public function handle(InboundEnvelope $envelope): Disposition
    {
        $headers = $envelope->headers;

        if ($headers->find(MESSAGE_TYPE) === self::TRIGGER_TYPE) {
            return $this->handleTrigger($envelope);
        }

        $messageId = $headers->get(MESSAGE_ID);

        $outbox = $this->outboxStorage->find($this->endpoint, $messageId);

        if ($outbox !== null) {
            $this->dispatchOutbox($messageId, $outbox);

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

            $outbox = new Outbox($outboundEnvelopes);

            if (!$this->storeOutbox($txScope, $messageId, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $this->endpoint,
                    'message_id' => $messageId,
                ]);

                return Disposition::Ack;
            }

            $txScope->commit();
        } finally {
            $txScope->rollback();
        }

        $this->dispatchOutbox($messageId, $outbox);

        return Disposition::Ack;
    }

    private function handleTrigger(InboundEnvelope $envelope): Disposition
    {
        try {
            $payload = json_decode($envelope->payload, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new DeserializationFailed('Outbox trigger payload is not valid JSON.', previous: $exception);
        }

        if (!(\is_array($payload) && \is_string($messageId = $payload['message_id'] ?? null) && $messageId !== '')) {
            throw new DeserializationFailed('Outbox trigger payload must contain a non-empty "message_id".');
        }

        $outbox = $this->outboxStorage->find($this->endpoint, $messageId);

        if ($outbox !== null) {
            $this->dispatchOutbox($messageId, $outbox);

            return Disposition::Ack;
        }

        /**
         * Defensive check: {@see DiscardExpiredMessagesMiddleware} should normally discard expired messages.
         */
        if ($envelope->headers->get(EXPIRES_AT) <= $this->clock->now()) {
            return Disposition::Ack;
        }

        $this->logger->debug('Outbox trigger arrived before outbox record is visible.', [
            'endpoint' => $this->endpoint,
            'message_id' => $messageId,
        ]);

        $this->dispatcher->dispatch([
            new OutboundEnvelope(
                operation: Operation::Send,
                address: $this->endpoint,
                payload: $envelope->payload,
                headers: $envelope->headers,
                delay: $this->triggerRetryInterval,
            ),
        ]);

        return Disposition::Nack;
    }

    /**
     * @param TransactionScope<Tx> $txScope
     * @param non-empty-string $messageId
     */
    private function storeOutbox(TransactionScope $txScope, string $messageId, Outbox $outbox): bool
    {
        if ($txScope->hasBegun) {
            return $this->outboxStorage->storeInTransaction($txScope->transaction, $this->endpoint, $messageId, $outbox);
        }

        return $this->outboxStorage->store($this->endpoint, $messageId, $outbox);
    }

    /**
     * @param non-empty-string $messageId
     */
    private function dispatchOutbox(string $messageId, Outbox $outbox): void
    {
        if ($outbox->dispatched || $outbox->envelopes === []) {
            return;
        }

        $this->dispatcher->dispatch($outbox->envelopes);
        $this->outboxStorage->markDispatched($this->endpoint, $messageId);
    }
}
