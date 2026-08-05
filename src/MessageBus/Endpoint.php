<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\Headers;
use Thesis\Headers\HeaderException;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Handling\NoHandler;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Identification\UuidV7Generator;
use Thesis\MessageBus\Internal\ConsumerMiddlewareStack;
use Thesis\MessageBus\Internal\DiscardExpiredMessagesMiddleware;
use Thesis\MessageBus\Internal\EndpointTopology;
use Thesis\MessageBus\Internal\FailureHandlingMiddleware;
use Thesis\MessageBus\Internal\HandlerExecutor;
use Thesis\MessageBus\Internal\ImmediateMessageHandler;
use Thesis\MessageBus\Internal\InboundMessageFactory;
use Thesis\MessageBus\Internal\MessageMetadataRegistry;
use Thesis\MessageBus\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Internal\OutboxRuntime;
use Thesis\MessageBus\Internal\RequeueOnUnhandledFailureMiddleware;
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
use Thesis\MessageBus\Recoverability\DeadLetterStorage;
use Thesis\MessageBus\Recoverability\LinearRetryPolicy;
use Thesis\MessageBus\Recoverability\RecoverabilityPolicies;
use Thesis\MessageBus\Recoverability\RecoverabilityPolicy;
use Thesis\MessageBus\Recoverability\UnrecoverableErrorPolicy;
use Thesis\MessageBus\Serialization\Deserializer;
use Thesis\MessageBus\Serialization\MessageDeserializationFailed;
use Thesis\MessageBus\Serialization\MessageSerializationFailed;
use Thesis\MessageBus\Serialization\Serializer;
use Thesis\MessageBus\Transport\Consumer;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\Receiver;
use Thesis\MessageBus\Transport\SubscriptionConfigurator;
use Thesis\MessageBus\Transport\TransactionalDispatcher;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;
use Thesis\Time\WallClock;

/**
 * @api
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
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function outbox(
        string $name,
        HandlerRegistry $handlerRegistry,
        Dispatcher&Receiver&SubscriptionConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        OutboxStorage $outboxStorage,
        DeadLetterStorage $deadLetterStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        array $consumerMiddleware = [],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
        ?TimeSpan $outboxTriggerTtl = null,
        ?TimeSpan $outboxTriggerRetryInterval = null,
    ): self {
        $typeResolver = new MessageTypeResolvers($messageTypeResolvers);
        $messageMetadataRegistry = new MessageMetadataRegistry(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: $typeResolver,
            commandRouter: new CommandRouters($commandRouters),
        );
        $outboundEnvelopeFactory = new OutboundEnvelopeFactory(
            messageMetadataRegistry: $messageMetadataRegistry,
            serializer: $serializer,
            idGenerator: $idGenerator,
        );
        $runtime = new OutboxRuntime(
            endpoint: $name,
            handlerExecutor: new HandlerExecutor(
                endpoint: $name,
                handlerRegistry: $handlerRegistry,
                outboundEnvelopeFactory: $outboundEnvelopeFactory,
                dispatcher: $transport,
            ),
            inboundMessageFactory: InboundMessageFactory::fromClasses(
                knownClasses: $handlerRegistry->messageClasses,
                typeResolver: $typeResolver,
                deserializer: $serializer,
            ),
            dispatcher: $transport,
            transactionScopeFactory: $transactionScopeFactory,
            outboxStorage: $outboxStorage,
            logger: $logger,
            idGenerator: $idGenerator,
            clock: $clock,
            triggerTtl: $outboxTriggerTtl,
            triggerRetryInterval: $outboxTriggerRetryInterval,
        );

        return new self(
            name: $name,
            immediateHandler: $runtime,
            consumerHandler: ConsumerMiddlewareStack::from(
                endpoint: $name,
                handler: $runtime,
                middlewares: [
                    new RequeueOnUnhandledFailureMiddleware($logger),
                    new DiscardExpiredMessagesMiddleware($clock, $logger),
                    new FailureHandlingMiddleware(
                        policy: new RecoverabilityPolicies([
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
                        deadLetterStorage: $deadLetterStorage,
                        clock: $clock,
                        logger: $logger,
                    ),
                    ...$consumerMiddleware,
                ],
            ),
            outboundEnvelopeFactory: $outboundEnvelopeFactory,
            dispatcher: $transport,
            receiver: $transport,
            topology: new EndpointTopology(
                endpoint: $name,
                messageClasses: $handlerRegistry->messageClasses,
                messageMetadataRegistry: $messageMetadataRegistry,
                subscriptionConfigurator: $transport,
                logger: $logger,
            ),
        );
    }

    /**
     * @template STx of object
     * @param non-empty-string $name
     * @param HandlerRegistry<STx> $handlerRegistry
     * @param TransactionalDispatcher<STx>&Receiver&SubscriptionConfigurator $transport
     * @param TransactionScopeFactory<STx> $transactionScopeFactory
     * @param Deduplicator<STx> $deduplicator
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<MessageTypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function transactional(
        string $name,
        HandlerRegistry $handlerRegistry,
        TransactionalDispatcher&Receiver&SubscriptionConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        Deduplicator $deduplicator,
        DeadLetterStorage $deadLetterStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        array $consumerMiddleware = [],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
    ): self {
        $typeResolver = new MessageTypeResolvers($messageTypeResolvers);
        $messageMetadataRegistry = new MessageMetadataRegistry(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: $typeResolver,
            commandRouter: new CommandRouters($commandRouters),
        );
        $outboundEnvelopeFactory = new OutboundEnvelopeFactory(
            messageMetadataRegistry: $messageMetadataRegistry,
            serializer: $serializer,
            idGenerator: $idGenerator,
        );
        $runtime = new TransactionalDispatcherRuntime(
            endpoint: $name,
            handlerExecutor: new HandlerExecutor(
                endpoint: $name,
                handlerRegistry: $handlerRegistry,
                outboundEnvelopeFactory: $outboundEnvelopeFactory,
                dispatcher: $transport,
            ),
            inboundMessageFactory: InboundMessageFactory::fromClasses(
                knownClasses: $handlerRegistry->messageClasses,
                typeResolver: $typeResolver,
                deserializer: $serializer,
            ),
            dispatcher: $transport,
            transactionScopeFactory: $transactionScopeFactory,
            deduplicator: $deduplicator,
            logger: $logger,
        );

        return new self(
            name: $name,
            immediateHandler: $runtime,
            consumerHandler: ConsumerMiddlewareStack::from(
                endpoint: $name,
                handler: $runtime,
                middlewares: [
                    new RequeueOnUnhandledFailureMiddleware($logger),
                    new DiscardExpiredMessagesMiddleware($clock, $logger),
                    new FailureHandlingMiddleware(
                        policy: new RecoverabilityPolicies([
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
                        deadLetterStorage: $deadLetterStorage,
                        clock: $clock,
                        logger: $logger,
                    ),
                    ...$consumerMiddleware,
                ],
            ),
            outboundEnvelopeFactory: $outboundEnvelopeFactory,
            dispatcher: $transport,
            receiver: $transport,
            topology: new EndpointTopology(
                endpoint: $name,
                messageClasses: $handlerRegistry->messageClasses,
                messageMetadataRegistry: $messageMetadataRegistry,
                subscriptionConfigurator: $transport,
                logger: $logger,
            ),
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function __construct(
        public string $name,
        private ImmediateMessageHandler $immediateHandler,
        private ConsumerHandler $consumerHandler,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
        private Dispatcher $dispatcher,
        private Receiver $receiver,
        private EndpointTopology $topology,
    ) {}

    public function setup(): void
    {
        $this->topology->setup();
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
    public function dispatch(Send|Publish $intent): void
    {
        $this->dispatcher->dispatch([
            $this->outboundEnvelopeFactory->build(
                intent: $intent,
                originEndpoint: $this->name,
            ),
        ]);
    }

    public function handle(object $message, Headers $headers = new Headers()): void
    {
        $this->immediateHandler->handleImmediately(
            message: $message,
            headers: $headers,
        );
    }

    public function startConsumer(): Consumer
    {
        return $this->receiver->startConsumer(
            queue: $this->name,
            handler: $this->consumerHandler,
        );
    }
}
