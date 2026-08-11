<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\Headers;
use Thesis\Headers\HeaderException;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Consumption\Deduplicator;
use Thesis\MessageBus\Consumption\Internal\ConsumerMiddlewareStack;
use Thesis\MessageBus\Consumption\Internal\DiscardExpiredMessagesMiddleware;
use Thesis\MessageBus\Consumption\Internal\ImmediateMessageHandler;
use Thesis\MessageBus\Consumption\Internal\InboundMessageFactory;
use Thesis\MessageBus\Consumption\Internal\MessageRetention;
use Thesis\MessageBus\Consumption\Internal\OutboxRuntime;
use Thesis\MessageBus\Consumption\Internal\RecoverabilityMiddleware;
use Thesis\MessageBus\Consumption\Internal\RequeueOnUnhandledFailureMiddleware;
use Thesis\MessageBus\Consumption\Internal\SetupSubscription;
use Thesis\MessageBus\Consumption\Internal\TransactionalRuntime;
use Thesis\MessageBus\Consumption\OutboxStorage;
use Thesis\MessageBus\Consumption\Recoverability\LinearRetryPolicy;
use Thesis\MessageBus\Consumption\Recoverability\RecoverabilityPolicies;
use Thesis\MessageBus\Consumption\Recoverability\RecoverabilityPolicy;
use Thesis\MessageBus\Consumption\Recoverability\UnrecoverableErrorPolicy;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Handling\Internal\HandlerExecutor;
use Thesis\MessageBus\Handling\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Handling\NoHandler;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Identification\UuidV7Generator;
use Thesis\MessageBus\Metadata\AttributeConvention;
use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Metadata\InvalidKind;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageClassifiers;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;
use Thesis\MessageBus\Protocol\ClassBasedTypeResolver;
use Thesis\MessageBus\Protocol\DeserializationFailed;
use Thesis\MessageBus\Protocol\Deserializer;
use Thesis\MessageBus\Protocol\InvalidType;
use Thesis\MessageBus\Protocol\SerializationFailed;
use Thesis\MessageBus\Protocol\Serializer;
use Thesis\MessageBus\Protocol\TypeResolver;
use Thesis\MessageBus\Protocol\TypeResolvers;
use Thesis\MessageBus\Routing\CannotRouteCommand;
use Thesis\MessageBus\Routing\CommandRouter;
use Thesis\MessageBus\Routing\CommandRouters;
use Thesis\MessageBus\Routing\MapCommandRouter;
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
     * @param TransactionScopeFactory<STx> $transactionScopeFactory
     * @param OutboxStorage<STx> $outboxStorage
     * @param list<CommandRouter> $commandRouters
     * @param non-empty-list<MessageClassifier> $messageClassifiers
     * @param non-empty-list<TypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @param non-empty-string $deadLetterQueue
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function outbox(
        string $name,
        HandlerRegistry $handlerRegistry,
        Dispatcher&Receiver&SubscriptionConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        OutboxStorage $outboxStorage,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeConvention()],
        array $messageClassifiers = [new AttributeConvention()],
        array $messageTypeResolvers = [new AttributeConvention(), new ClassBasedTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        string $deadLetterQueue = self::DLQ,
        array $consumerMiddleware = [],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
        ?TimeSpan $outboxTriggerTtl = null,
        ?TimeSpan $outboxTriggerRetryInterval = null,
    ): self {
        $messageMetadataFactory = new MessageMetadataFactory(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: new TypeResolvers($messageTypeResolvers),
        );
        $outboundEnvelopeFactory = new OutboundEnvelopeFactory(
            messageMetadataFactory: $messageMetadataFactory,
            commandRouter: new CommandRouters([
                ...$commandRouters,
                new MapCommandRouter(array_fill_keys($handlerRegistry->messageClasses, $name)),
            ]),
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
                messageMetadataFactory: $messageMetadataFactory,
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
                    new RecoverabilityMiddleware(
                        policy: new RecoverabilityPolicies([
                            new UnrecoverableErrorPolicy([
                                SerializationFailed::class,
                                DeserializationFailed::class,
                                InvalidType::class,
                                InvalidKind::class,
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
            retention: new MessageRetention(
                endpoint: $name,
                purger: $outboxStorage->purgeDispatchedBefore(...),
                logger: $logger,
                clock: $clock,
            ),
            setups: [
                static fn() => $transport->createQueue($name),
                static fn() => $transport->createQueue($deadLetterQueue),
                new SetupSubscription(
                    endpoint: $name,
                    messageMetadataFactory: $messageMetadataFactory,
                    subscriptionConfigurator: $transport,
                    messageClasses: $handlerRegistry->messageClasses,
                ),
                $outboxStorage->setup(...),
            ],
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
     * @param non-empty-list<MessageClassifier> $messageClassifiers
     * @param non-empty-list<TypeResolver> $messageTypeResolvers
     * @param list<RecoverabilityPolicy> $recoverabilityPolicies
     * @param non-empty-string $deadLetterQueue
     * @param list<ConsumerMiddleware> $consumerMiddleware
     */
    public static function transactional(
        string $name,
        HandlerRegistry $handlerRegistry,
        TransactionalDispatcher&Receiver&SubscriptionConfigurator $transport,
        TransactionScopeFactory $transactionScopeFactory,
        Deduplicator $deduplicator,
        Serializer&Deserializer $serializer,
        LoggerInterface $logger = new NullLogger(),
        array $commandRouters = [new AttributeConvention()],
        array $messageClassifiers = [new AttributeConvention()],
        array $messageTypeResolvers = [new AttributeConvention(), new ClassBasedTypeResolver()],
        array $recoverabilityPolicies = [new LinearRetryPolicy()],
        string $deadLetterQueue = self::DLQ,
        array $consumerMiddleware = [],
        IdGenerator $idGenerator = new UuidV7Generator(),
        ClockInterface $clock = new WallClock(),
    ): self {
        $messageMetadataFactory = new MessageMetadataFactory(
            classifier: new MessageClassifiers($messageClassifiers),
            typeResolver: new TypeResolvers($messageTypeResolvers),
        );
        $outboundEnvelopeFactory = new OutboundEnvelopeFactory(
            messageMetadataFactory: $messageMetadataFactory,
            commandRouter: new CommandRouters([
                ...$commandRouters,
                new MapCommandRouter(array_fill_keys($handlerRegistry->messageClasses, $name)),
            ]),
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
                messageMetadataFactory: $messageMetadataFactory,
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
                    new RecoverabilityMiddleware(
                        policy: new RecoverabilityPolicies([
                            new UnrecoverableErrorPolicy([
                                SerializationFailed::class,
                                DeserializationFailed::class,
                                InvalidType::class,
                                InvalidKind::class,
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
            retention: new MessageRetention(
                endpoint: $name,
                purger: $deduplicator->purgeHandledBefore(...),
                logger: $logger,
                clock: $clock,
            ),
            setups: [
                static fn() => $transport->createQueue($name),
                static fn() => $transport->createQueue($deadLetterQueue),
                new SetupSubscription(
                    endpoint: $name,
                    messageMetadataFactory: $messageMetadataFactory,
                    subscriptionConfigurator: $transport,
                    messageClasses: $handlerRegistry->messageClasses,
                ),
                $deduplicator->setup(...),
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
        private MessageRetention $retention,
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
     * @throws InvalidIntent
     * @throws CannotRouteCommand
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
                destination: $endpoint,
                headers: $headers,
                delay: $delay,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidIntent
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
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
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

    public function purgeRetainedMessages(?TimeSpan $retentionPeriod = null): int
    {
        return $this->retention->purge($retentionPeriod);
    }

    /**
     * Starts periodic retention cleanup.
     *
     * @return \Closure(): void a stop callback
     */
    public function startRetentionPurger(?TimeSpan $retentionPeriod = null, ?TimeSpan $purgeInterval = null): \Closure
    {
        return $this->retention->startPurger($retentionPeriod, $purgeInterval);
    }
}
