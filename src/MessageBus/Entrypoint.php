<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Handling\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Identification\UuidV7Generator;
use Thesis\MessageBus\Metadata\AttributeConvention;
use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageClassifiers;
use Thesis\MessageBus\Protocol\ClassBasedTypeResolver;
use Thesis\MessageBus\Protocol\RoutedCorrelationId;
use Thesis\MessageBus\Protocol\Serializer;
use Thesis\MessageBus\Protocol\TypeResolver;
use Thesis\MessageBus\Protocol\TypeResolvers;
use Thesis\MessageBus\Routing\CannotRouteCommand;
use Thesis\MessageBus\Routing\CommandRouter;
use Thesis\MessageBus\Routing\CommandRouters;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class Entrypoint
{
    /**
     * @param non-empty-string $name
     * @param list<CommandRouter> $commandRouters
     * @param list<MessageClassifier> $messageClassifiers
     * @param list<TypeResolver> $messageTypeResolvers
     */
    public static function build(
        string $name,
        Dispatcher $dispatcher,
        Serializer $serializer,
        array $commandRouters = [new AttributeConvention()],
        array $messageClassifiers = [new AttributeConvention()],
        array $messageTypeResolvers = [new AttributeConvention(), new ClassBasedTypeResolver()],
        IdGenerator $idGenerator = new UuidV7Generator(),
    ): self {
        return new self(
            name: $name,
            dispatcher: $dispatcher,
            outboundEnvelopeFactory: new OutboundEnvelopeFactory(
                messageMetadataFactory: new MessageMetadataFactory(
                    classifier: new MessageClassifiers($messageClassifiers),
                    typeResolver: new TypeResolvers($messageTypeResolvers),
                ),
                commandRouter: new CommandRouters($commandRouters),
                serializer: $serializer,
                idGenerator: $idGenerator,
            ),
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function __construct(
        public string $name,
        private Dispatcher $dispatcher,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
    ) {}

    /**
     * @param ?non-empty-string $endpoint
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    public function send(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Send(
                command: $command,
                destination: $endpoint,
                headers: $headers,
                delay: $delay,
                ttl: $ttl,
                correlationId: $correlationId,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     */
    public function publish(
        object $event,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Publish(
                event: $event,
                headers: $headers,
                ttl: $ttl,
                correlationId: $correlationId,
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
}
