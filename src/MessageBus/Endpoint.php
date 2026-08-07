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
use Thesis\MessageBus\Internal\HandlerExecutor;
use Thesis\MessageBus\Internal\ImmediateMessageHandler;
use Thesis\MessageBus\Internal\InboundMessageFactory;
use Thesis\MessageBus\Internal\MessageMetadataRegistry;
use Thesis\MessageBus\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Internal\OutboxRuntime;
use Thesis\MessageBus\Internal\RecoverabilityMiddleware;
use Thesis\MessageBus\Internal\RequeueOnUnhandledFailureMiddleware;
use Thesis\MessageBus\Internal\SetupSubscription;
use Thesis\MessageBus\Internal\TransactionalRuntime;
use Thesis\MessageBus\Metadata\AttributeCommandRouter;
use Thesis\MessageBus\Metadata\AttributeMessageClassifier;
use Thesis\MessageBus\Metadata\AttributeMessageTypeResolver;
use Thesis\MessageBus\Metadata\ClassBasedMessageTypeResolver;
use Thesis\MessageBus\Metadata\CommandRouter;
use Thesis\MessageBus\Metadata\CommandRouters;
use Thesis\MessageBus\Metadata\InvalidMetadata;
use Thesis\MessageBus\Metadata\MapCommandRouter;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageClassifiers;
use Thesis\MessageBus\Metadata\MessageTypeResolver;
use Thesis\MessageBus\Metadata\MessageTypeResolvers;
use Thesis\MessageBus\Persistence\Connection;
use Thesis\MessageBus\Processing\Deduplicator;
use Thesis\MessageBus\Processing\OutboxStorage;
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
    private const string DLQ = 'dlq';

    /**
     * @template STx of object
     * @param non-empty-string $name
     * @param HandlerRegistry<STx> $handlerRegistry
     * @param Connection<STx> $connection
     * @param OutboxStorage<STx> $outboxStorage
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<MessageTypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @param non-empty-string $deadLetterQueue
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function outbox(
        string $name,
        HandlerRegistry $handlerRegistry,
        Dispatcher&Receiver&SubscriptionConfigurator $transport,
        Connection $connection,
        OutboxStorage $outboxStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        string $deadLetterQueue = self::DLQ,
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
            commandRouter: new CommandRouters([
                ...$commandRouters,
                new MapCommandRouter(array_fill_keys($handlerRegistry->messageClasses, $name)),
            ]),
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
            connection: $connection,
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
                    new RecoverabilityMiddleware(
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
                        deadLetterQueue: $deadLetterQueue,
                        clock: $clock,
                        logger: $logger,
                    ),
                    ...$consumerMiddleware,
                ],
            ),
            outboundEnvelopeFactory: $outboundEnvelopeFactory,
            dispatcher: $transport,
            receiver: $transport,
            setups: [
                static fn() => $transport->createQueue($name),
                static fn() => $transport->createQueue($deadLetterQueue),
                new SetupSubscription(
                    endpoint: $name,
                    messageClasses: $handlerRegistry->messageClasses,
                    messageMetadataRegistry: $messageMetadataRegistry,
                    subscriptionConfigurator: $transport,
                ),
                static fn() => $outboxStorage->setup($name),
            ],
        );
    }

    /**
     * @template STx of object
     * @param non-empty-string $name
     * @param HandlerRegistry<STx> $handlerRegistry
     * @param TransactionalDispatcher<STx>&Receiver&SubscriptionConfigurator $transport
     * @param Connection<STx> $connection
     * @param Deduplicator<STx> $deduplicator
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<MessageTypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @param non-empty-string $deadLetterQueue
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function transactional(
        string $name,
        HandlerRegistry $handlerRegistry,
        TransactionalDispatcher&Receiver&SubscriptionConfigurator $transport,
        Connection $connection,
        Deduplicator $deduplicator,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        string $deadLetterQueue = self::DLQ,
        array $consumerMiddleware = [],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
    ): self {
        $typeResolver = new MessageTypeResolvers($messageTypeResolvers);
        $messageMetadataRegistry = new MessageMetadataRegistry(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: $typeResolver,
            commandRouter: new CommandRouters([
                ...$commandRouters,
                new MapCommandRouter(array_fill_keys($handlerRegistry->messageClasses, $name)),
            ]),
        );
        $outboundEnvelopeFactory = new OutboundEnvelopeFactory(
            messageMetadataRegistry: $messageMetadataRegistry,
            serializer: $serializer,
            idGenerator: $idGenerator,
        );
        $runtime = new TransactionalRuntime(
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
            connection: $connection,
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
                    new RecoverabilityMiddleware(
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
                        deadLetterQueue: $deadLetterQueue,
                        clock: $clock,
                        logger: $logger,
                    ),
                    ...$consumerMiddleware,
                ],
            ),
            outboundEnvelopeFactory: $outboundEnvelopeFactory,
            dispatcher: $transport,
            receiver: $transport,
            setups: [
                static fn() => $transport->createQueue($name),
                static fn() => $transport->createQueue($deadLetterQueue),
                new SetupSubscription(
                    endpoint: $name,
                    messageClasses: $handlerRegistry->messageClasses,
                    messageMetadataRegistry: $messageMetadataRegistry,
                    subscriptionConfigurator: $transport,
                ),
                static fn() => $deduplicator->setup($name),
            ],
        );
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-list<callable(): void> $setups
     */
    private function __construct(
        public string $name,
        private ImmediateMessageHandler $immediateHandler,
        private ConsumerHandler $consumerHandler,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
        private Dispatcher $dispatcher,
        private Receiver $receiver,
        private array $setups,
    ) {}

    public function setup(): void
    {
        foreach ($this->setups as $setup) {
            $setup();
        }
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
