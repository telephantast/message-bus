<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Persistence\TransactionScope;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Processing\Outbox;
use Thesis\MessageBus\Processing\OutboxNotFound;
use Thesis\MessageBus\Processing\OutboxStorage;
use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Serialization\MessageDeserializationFailed;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\CAUSE_ID;
use const Thesis\MessageBus\CONTENT_TYPE;
use const Thesis\MessageBus\CONVERSATION_ID;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\ORIGIN_ENDPOINT;
use const Thesis\MessageBus\SENT_AT;

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
 * @implements Runtime<Tx>
 */
final readonly class OutboxRuntime implements Runtime
{
    private const string TRIGGER_TYPE = 'thesis.service.dispatch_outbox';

    public static function defaultTriggerTtl(): TimeSpan
    {
        return TimeSpan::fromMinutes(30);
    }

    private TimeSpan $triggerTtl;

    /**
     * @param TransactionScopeFactory<Tx> $transactionScopeFactory
     * @param OutboxStorage<Tx> $outboxStorage
     */
    public function __construct(
        private Dispatcher $dispatcher,
        private TransactionScopeFactory $transactionScopeFactory,
        private OutboxStorage $outboxStorage,
        private IdGenerator $idGenerator,
        private ClockInterface $clock,
        ?TimeSpan $triggerTtl,
        private LoggerInterface $logger,
    ) {
        $this->triggerTtl = $triggerTtl ?? self::defaultTriggerTtl();
    }

    public function dispatch(string $endpoint, array $envelopes): void
    {
        if (\count($envelopes) === 1) {
            $this->dispatcher->dispatch($envelopes);

            return;
        }

        $this->dispatchIdempotently(
            id: new ProcessingId($endpoint, $this->idGenerator->generateId()),
            envelopes: $envelopes,
        );
    }

    public function dispatchIdempotently(ProcessingId $id, array $envelopes): void
    {
        $outbox = new Outbox($envelopes);

        $txScope = $this->transactionScopeFactory->create();
        $txScope->begin();

        try {
            if (!$this->outboxStorage->storeInTransaction($txScope->transaction, $id, $outbox)) {
                $this->logger->debug('Outbox already exists; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->rollbackIfActive();

                return;
            }

            $this->dispatchTrigger($id);

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
                $id = new ProcessingId(
                    endpoint: $endpoint,
                    messageId: $this->idGenerator->generateId(),
                );

                $outbox = new Outbox($outboundEnvelopes);

                $txScope->begin();

                if (!$this->outboxStorage->storeInTransaction($txScope->transaction, $id, $outbox)) {
                    $this->logger->debug('Outbox already exists; skipping.', [
                        'endpoint' => $id->endpoint,
                        'message_id' => $id->messageId,
                    ]);

                    $txScope->rollbackIfActive();

                    return;
                }

                $this->dispatchTrigger($id);
            }

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }
    }

    public function handleIdempotently(ProcessingId $id, callable $handler): void
    {
        $outbox = $this->outboxStorage->find($id);

        if ($outbox !== null) {
            $this->logger->debug('Outbox already exists; skipping.', [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ]);

            return;
        }

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $handler($txScope->transaction, $this->dispatcher);

            $outbox = new Outbox($outboundEnvelopes);

            if (!$outbox->dispatched) {
                $txScope->begin();
            }

            if (!$this->storeOutbox($txScope, $id, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->rollbackIfActive();

                return;
            }

            if (!$outbox->dispatched) {
                $this->dispatchTrigger($id);
            }

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }
    }

    public function consumeIdempotently(ProcessingId $id, InboundEnvelope $envelope, callable $handler): void
    {
        $headers = $envelope->headers;

        if ($headers->find(MESSAGE_TYPE) === self::TRIGGER_TYPE) {
            $this->handleTrigger($id->endpoint, $envelope);

            return;
        }

        $outbox = $this->outboxStorage->find($id);

        if ($outbox !== null) {
            $this->dispatchOutbox($id, $outbox);

            return;
        }

        $txScope = $this->transactionScopeFactory->create();

        try {
            $outboundEnvelopes = $handler($txScope->transaction, $this->dispatcher);

            $outbox = new Outbox($outboundEnvelopes);

            if (!$this->storeOutbox($txScope, $id, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->rollbackIfActive();

                return;
            }

            $txScope->commitIfBegun();
        } catch (\Throwable $exception) {
            $txScope->rollbackIfActive();

            throw $exception;
        }

        $this->dispatchOutbox($id, $outbox);
    }

    private function dispatchTrigger(ProcessingId $id): void
    {
        $this->dispatcher->dispatch([
            new OutboundEnvelope(
                operation: Operation::Send,
                address: $id->endpoint,
                payload: json_encode(['message_id' => $id->messageId], JSON_THROW_ON_ERROR),
                headers: new Headers()
                    ->with(CONTENT_TYPE, 'application/json')
                    ->with(MESSAGE_TYPE, self::TRIGGER_TYPE)
                    ->with(ORIGIN_ENDPOINT, $id->endpoint)
                    ->with(MESSAGE_ID, $this->idGenerator->generateId())
                    ->with(CAUSE_ID, $id->messageId)
                    ->with(CONVERSATION_ID, $id->messageId)
                    ->with(SENT_AT, $this->clock->now()),
            ),
        ]);
    }

    /**
     * @param non-empty-string $endpoint
     */
    private function handleTrigger(string $endpoint, InboundEnvelope $envelope): void
    {
        try {
            $payload = json_decode($envelope->payload, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new MessageDeserializationFailed('Outbox trigger payload is not valid JSON.', previous: $exception);
        }

        if (!(\is_array($payload) && \is_string($messageId = $payload['message_id'] ?? null) && $messageId !== '')) {
            throw new MessageDeserializationFailed('Outbox trigger payload must contain a non-empty "message_id".');
        }

        $id = new ProcessingId(
            endpoint: $endpoint,
            messageId: $messageId,
        );

        $outbox = $this->outboxStorage->find($id);

        if ($outbox === null) {
            $passed = TimeSpan::diff($this->clock->now(), $envelope->headers->get(SENT_AT));

            if ($passed->isGreaterThan($this->triggerTtl)) {
                $this->logger->warning('Outbox trigger expired without a matching outbox record.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                    'trigger_age_seconds' => $passed->toSeconds(),
                    'trigger_ttl_seconds' => $this->triggerTtl->toSeconds(),
                ]);

                return;
            }

            $this->logger->debug('Outbox trigger arrived before outbox record is visible.', [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
                'trigger_age_seconds' => $passed->toSeconds(),
                'trigger_ttl_seconds' => $this->triggerTtl->toSeconds(),
            ]);

            throw new OutboxNotFound($id);
        }

        $this->dispatchOutbox($id, $outbox);
    }

    /**
     * @param TransactionScope<Tx> $txScope
     */
    private function storeOutbox(TransactionScope $txScope, ProcessingId $id, Outbox $outbox): bool
    {
        if ($txScope->hasBegun) {
            return $this->outboxStorage->storeInTransaction($txScope->transaction, $id, $outbox);
        }

        return $this->outboxStorage->store($id, $outbox);
    }

    private function dispatchOutbox(ProcessingId $id, Outbox $outbox): void
    {
        if ($outbox->dispatched || $outbox->envelopes === []) {
            return;
        }

        $this->dispatcher->dispatch($outbox->envelopes);
        $this->outboxStorage->markDispatched($id);
    }
}
