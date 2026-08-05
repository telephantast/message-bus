<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Thesis\Headers;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Persistence\Connection;
use Thesis\MessageBus\Processing\Outbox;
use Thesis\MessageBus\Processing\OutboxStorage;
use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Serialization\MessageDeserializationFailed;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\Operation;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\CAUSE_ID;
use const Thesis\MessageBus\CONTENT_TYPE;
use const Thesis\MessageBus\CONVERSATION_ID;
use const Thesis\MessageBus\CREATED_AT;
use const Thesis\MessageBus\EXPIRES_AT;
use const Thesis\MessageBus\MESSAGE_ID;
use const Thesis\MessageBus\MESSAGE_TYPE;
use const Thesis\MessageBus\ORIGIN_ENDPOINT;

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
     * @param Connection<Tx> $connection
     * @param OutboxStorage<Tx> $outboxStorage
     * @param HandlerExecutor<Tx> $handlerExecutor
     */
    public function __construct(
        private string $endpoint,
        private HandlerExecutor $handlerExecutor,
        private InboundMessageFactory $inboundMessageFactory,
        private Dispatcher $dispatcher,
        private Connection $connection,
        private OutboxStorage $outboxStorage,
        private LoggerInterface $logger,
        private IdGenerator $idGenerator,
        private ClockInterface $clock,
        ?TimeSpan $triggerTtl,
        ?TimeSpan $triggerRetryInterval,
    ) {
        $this->triggerTtl = $triggerTtl ?? TimeSpan::fromMinutes(30);
        $this->triggerRetryInterval = $triggerRetryInterval ?? TimeSpan::fromMinutes(1);
    }

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
                $id = new ProcessingId(
                    endpoint: $this->endpoint,
                    messageId: $this->idGenerator->generateId(),
                );

                // because failed trigger dispatch should rollback storeOutbox()
                $txScope->begin();

                if (!$this->storeOutbox($txScope, $id, new Outbox($outboundEnvelopes))) {
                    throw new \LogicException('Outbox record could not be stored for a random processing id.');
                }

                $this->dispatchTrigger($id);
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

        $outbox = $this->outboxStorage->find($id);

        if ($outbox !== null) {
            $this->logger->debug('Outbox already exists; skipping.', [
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

            $outbox = new Outbox($outboundEnvelopes);

            if ($outboundEnvelopes !== []) {
                $txScope->begin();
            }

            if (!$this->storeOutbox($txScope, $id, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->close();

                return;
            }

            if ($outboundEnvelopes !== []) {
                $this->dispatchTrigger($id);
            }

            $txScope->commit();
        } catch (\Throwable $exception) {
            $txScope->close();

            throw $exception;
        }
    }

    private function dispatchTrigger(ProcessingId $id): void
    {
        $now = $this->clock->now();

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
                    ->with(CREATED_AT, $now)
                    ->with(EXPIRES_AT, $now->modify("{$this->triggerTtl->toMicroseconds()} microseconds")),
            ),
        ]);
    }

    public function handle(InboundEnvelope $envelope): Disposition
    {
        $headers = $envelope->headers;

        if ($headers->find(MESSAGE_TYPE) === self::TRIGGER_TYPE) {
            $this->handleTrigger($envelope);

            return Disposition::Ack;
        }

        $id = new ProcessingId(
            endpoint: $this->endpoint,
            messageId: $headers->get(MESSAGE_ID),
        );

        $outbox = $this->outboxStorage->find($id);

        if ($outbox !== null) {
            $this->dispatchOutbox($id, $outbox);

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

            $outbox = new Outbox($outboundEnvelopes);

            if (!$this->storeOutbox($txScope, $id, $outbox)) {
                $this->logger->debug('Outbox record was stored concurrently; skipping.', [
                    'endpoint' => $id->endpoint,
                    'message_id' => $id->messageId,
                ]);

                $txScope->close();

                return Disposition::Ack;
            }

            $txScope->commit();
        } catch (\Throwable $exception) {
            $txScope->close();

            throw $exception;
        }

        $this->dispatchOutbox($id, $outbox);

        return Disposition::Ack;
    }

    private function handleTrigger(InboundEnvelope $envelope): void
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
            endpoint: $this->endpoint,
            messageId: $messageId,
        );

        $outbox = $this->outboxStorage->find($id);

        if ($outbox !== null) {
            $this->dispatchOutbox($id, $outbox);

            return;
        }

        /**
         * Defensive check: {@see DiscardExpiredMessagesMiddleware} should normally discard expired messages.
         */
        if ($envelope->headers->get(EXPIRES_AT) <= $this->clock->now()) {
            return;
        }

        $this->logger->debug('Outbox trigger arrived before outbox record is visible.', [
            'endpoint' => $id->endpoint,
            'message_id' => $id->messageId,
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
    }

    /**
     * @param RuntimeTransactionScope<Tx> $txScope
     */
    private function storeOutbox(RuntimeTransactionScope $txScope, ProcessingId $id, Outbox $outbox): bool
    {
        if ($txScope->hasBegun) {
            return $this->outboxStorage->storeInTransaction($txScope->handle, $id, $outbox);
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
