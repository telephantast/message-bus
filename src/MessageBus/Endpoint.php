<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\Headers;
use Thesis\Headers\HeaderException;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Handling\NoHandler;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Identification\UuidV7Generator;
use Thesis\MessageBus\Internal\MessageMetadataRegistry;
use Thesis\MessageBus\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Internal\OutboxRuntime;
use Thesis\MessageBus\Internal\Recoverability;
use Thesis\MessageBus\Internal\Runtime;
use Thesis\MessageBus\Internal\RuntimeHandlerContext;
use Thesis\MessageBus\Internal\TransactionalDispatcherRuntime;
use Thesis\MessageBus\Metadata\AttributeCommandRouter;
use Thesis\MessageBus\Metadata\AttributeMessageClassifier;
use Thesis\MessageBus\Metadata\AttributeMessageTypeResolver;
use Thesis\MessageBus\Metadata\ClassBasedMessageTypeResolver;
use Thesis\MessageBus\Metadata\CommandRouter;
use Thesis\MessageBus\Metadata\CommandRouters;
use Thesis\MessageBus\Metadata\InvalidMetadata;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageClassifiers;
use Thesis\MessageBus\Metadata\MessageTypeResolver;
use Thesis\MessageBus\Metadata\MessageTypeResolvers;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Processing\Deduplicator;
use Thesis\MessageBus\Processing\OutboxStorage;
use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Recoverability\ChainRecoverabilityPolicy;
use Thesis\MessageBus\Recoverability\DeadLetterStorage;
use Thesis\MessageBus\Recoverability\LinearRetryPolicy;
use Thesis\MessageBus\Recoverability\RecoverabilityPolicy;
use Thesis\MessageBus\Recoverability\UnrecoverableErrorPolicy;
use Thesis\MessageBus\Serialization\Deserializer;
use Thesis\MessageBus\Serialization\MessageDeserializationFailed;
use Thesis\MessageBus\Serialization\MessageSerializationFailed;
use Thesis\MessageBus\Serialization\SerializedMessage;
use Thesis\MessageBus\Serialization\Serializer;
use Thesis\MessageBus\Transport\Consumer;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\OutboundEnvelope;
use Thesis\MessageBus\Transport\Receiver;
use Thesis\MessageBus\Transport\TopologyConfigurator;
use Thesis\MessageBus\Transport\TransactionalDispatcher;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;
use Thesis\Time\WallClock;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final readonly class Endpoint
{
    /**
     * @template STx of object
     * @param non-empty-string $name
     * @param HandlerRegistry<STx> $handlerRegistry
     * @param TransactionScopeFactory<STx> $transactionScopeFactory
     * @param OutboxStorage<STx> $outboxStorage
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<MessageTypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @return self<STx>
     */
    public static function outbox(
        string $name,
        HandlerRegistry $handlerRegistry,
        Dispatcher&Receiver&TopologyConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        OutboxStorage $outboxStorage,
        DeadLetterStorage $deadLetterStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
        ?TimeSpan $outboxTriggerTtl = null,
    ): self {
        $metadataRegistry = new MessageMetadataRegistry(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: new MessageTypeResolvers($messageTypeResolvers),
            commandRouter: new CommandRouters($commandRouters),
            knownClasses: $handlerRegistry->messageClasses,
        );

        return new self(
            name: $name,
            handlerRegistry: $handlerRegistry,
            runtime: new OutboxRuntime(
                dispatcher: $transport,
                transactionScopeFactory: $transactionScopeFactory,
                outboxStorage: $outboxStorage,
                idGenerator: $idGenerator,
                clock: $clock,
                triggerTtl: $outboxTriggerTtl,
                logger: $logger,
            ),
            receiver: $transport,
            topology: $transport,
            deserializer: $serializer,
            messageMetadataRegistry: $metadataRegistry,
            outboundEnvelopeFactory: new OutboundEnvelopeFactory(
                messageMetadataRegistry: $metadataRegistry,
                serializer: $serializer,
                idGenerator: $idGenerator,
            ),
            recoverability: new Recoverability(
                policy: new ChainRecoverabilityPolicy([
                    new UnrecoverableErrorPolicy([
                        MessageSerializationFailed::class,
                        MessageDeserializationFailed::class,
                        InvalidMetadata::class,
                        NoHandler::class,
                        HeaderException::class,
                    ]),
                    ...$recoverabilityPolicies,
                ]),
                dispatcher: $transport,
                deadLetterStore: $deadLetterStorage,
                clock: $clock,
                logger: $logger,
            ),
            logger: $logger,
        );
    }

    /**
     * @template STx of object
     * @param non-empty-string $name
     * @param HandlerRegistry<STx> $handlerRegistry
     * @param TransactionalDispatcher<STx>&Receiver&TopologyConfigurator $transport
     * @param TransactionScopeFactory<STx> $transactionScopeFactory
     * @param Deduplicator<STx> $deduplicator
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<MessageTypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @return self<STx>
     */
    public static function transactional(
        string $name,
        HandlerRegistry $handlerRegistry,
        TransactionalDispatcher&Receiver&TopologyConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        Deduplicator $deduplicator,
        DeadLetterStorage $deadLetterStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
    ): self {
        $metadataRegistry = new MessageMetadataRegistry(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: new MessageTypeResolvers($messageTypeResolvers),
            commandRouter: new CommandRouters($commandRouters),
            knownClasses: $handlerRegistry->messageClasses,
        );

        return new self(
            name: $name,
            handlerRegistry: $handlerRegistry,
            runtime: new TransactionalDispatcherRuntime(
                dispatcher: $transport,
                transactionScopeFactory: $transactionScopeFactory,
                deduplicator: $deduplicator,
                logger: $logger,
            ),
            receiver: $transport,
            topology: $transport,
            deserializer: $serializer,
            messageMetadataRegistry: $metadataRegistry,
            outboundEnvelopeFactory: new OutboundEnvelopeFactory(
                messageMetadataRegistry: $metadataRegistry,
                serializer: $serializer,
                idGenerator: $idGenerator,
            ),
            recoverability: new Recoverability(
                policy: new ChainRecoverabilityPolicy([
                    new UnrecoverableErrorPolicy([
                        MessageSerializationFailed::class,
                        MessageDeserializationFailed::class,
                        InvalidMetadata::class,
                        NoHandler::class,
                        HeaderException::class,
                    ]),
                    ...$recoverabilityPolicies,
                ]),
                dispatcher: $transport,
                deadLetterStore: $deadLetterStorage,
                clock: $clock,
                logger: $logger,
            ),
            logger: $logger,
        );
    }

    /**
     * @param non-empty-string $name
     * @param HandlerRegistry<Tx> $handlerRegistry
     * @param Runtime<Tx> $runtime
     */
    private function __construct(
        public string $name,
        private HandlerRegistry $handlerRegistry,
        private Runtime $runtime,
        private Receiver $receiver,
        private TopologyConfigurator $topology,
        private Deserializer $deserializer,
        private MessageMetadataRegistry $messageMetadataRegistry,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
        private Recoverability $recoverability,
        private LoggerInterface $logger,
    ) {}

    public function setup(): void
    {
        $eventTypes = [];

        foreach ($this->handlerRegistry->messageClasses as $messageClass) {
            $metadata = $this->messageMetadataRegistry->forClass($messageClass);

            if ($metadata->isEvent) {
                $eventTypes[] = $metadata->type;
            }
        }

        $eventTypes = array_values(array_unique($eventTypes));

        $this->logger->debug('Subscribing endpoint to events.', [
            'endpoint' => $this->name,
            'event_types' => $eventTypes,
        ]);

        $this->topology->subscribeEndpointToEvents($this->name, $eventTypes);
    }

    /**
     * @param ?non-empty-string $endpoint
     *
     * @throws InvalidOutboundMessage
     */
    public function send(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Send(
                command: $command,
                destinationEndpoint: $endpoint,
                headers: $headers,
                delay: $delay,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidOutboundMessage
     */
    public function publish(
        object $event,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Publish(
                event: $event,
                headers: $headers,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @no-named-arguments
     */
    public function dispatch(Send|Publish ...$intents): void
    {
        if ($intents === []) {
            return;
        }

        $this->runtime->dispatch(
            endpoint: $this->name,
            envelopes: array_map(
                fn(object $intent) => $this->outboundEnvelopeFactory->build(
                    intent: $intent,
                    originEndpoint: $this->name,
                ),
                $intents,
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @param non-empty-string $id
     */
    public function dispatchIdempotently(string $id, Send|Publish ...$intents): void
    {
        if ($intents === []) {
            return;
        }

        $this->runtime->dispatchIdempotently(
            id: $this->processingIdFor($id),
            envelopes: array_map(
                fn(object $intent) => $this->outboundEnvelopeFactory->build(
                    intent: $intent,
                    originEndpoint: $this->name,
                ),
                $intents,
            ),
        );
    }

    public function handle(object $message, Headers $headers = new Headers()): void
    {
        $messageId = $headers->find(MESSAGE_ID);

        if ($messageId === null) {
            $this->runtime->handle(
                endpoint: $this->name,
                handler: fn(object $tx, Dispatcher $d) => $this->handleMessage($message, $headers, $tx, $d),
            );

            return;
        }

        $this->runtime->handleIdempotently(
            id: $this->processingIdFor($messageId),
            handler: fn(object $tx, Dispatcher $d) => $this->handleMessage($message, $headers, $tx, $d),
        );
    }

    public function startConsumer(): Consumer
    {
        return $this->receiver->startConsumer(
            endpoint: $this->name,
            handler: fn(InboundEnvelope $envelope) => $this->recoverability->process(
                endpoint: $this->name,
                envelope: $envelope,
                operation: fn() => $this->runtime->consumeIdempotently(
                    id: $this->processingIdFor($envelope->headers->get(MESSAGE_ID)),
                    envelope: $envelope,
                    handler: fn(object $tx, Dispatcher $d) => $this->handleEnvelope($envelope, $tx, $d),
                ),
            ),
        );
    }

    /**
     * @param Tx $transaction
     * @return list<OutboundEnvelope>
     */
    private function handleEnvelope(InboundEnvelope $envelope, object $transaction, Dispatcher $dispatcher): array
    {
        $headers = $envelope->headers;

        $messageClass = $this
            ->messageMetadataRegistry
            ->forType($headers->get(MESSAGE_TYPE))
            ->class;

        $message = $this->deserializer->deserialize(
            serializedMessage: new SerializedMessage(
                payload: $envelope->payload,
                contentType: $headers->find(CONTENT_TYPE),
                contentEncoding: $headers->find(CONTENT_ENCODING),
            ),
            messageClass: $messageClass,
        );

        return $this->handleMessage($message, $envelope->headers, $transaction, $dispatcher);
    }

    /**
     * @param Tx $transaction
     * @return list<OutboundEnvelope>
     */
    private function handleMessage(object $message, Headers $headers, object $transaction, Dispatcher $dispatcher): array
    {
        $handler = $this->handlerRegistry->handlerFor($message::class)
            ?? throw new NoHandler($message::class);

        $context = new RuntimeHandlerContext(
            endpoint: $this->name,
            headers: $headers,
            envelopeFactory: $this->outboundEnvelopeFactory,
            dispatcher: $dispatcher,
        );

        try {
            $handler($message, $context, $transaction);
        } finally {
            $context->disableDispatch();
        }

        return $context->outboundEnvelopes;
    }

    /**
     * @param non-empty-string $messageId
     */
    private function processingIdFor(string $messageId): ProcessingId
    {
        return new ProcessingId(
            endpoint: $this->name,
            messageId: $messageId,
        );
    }
}
