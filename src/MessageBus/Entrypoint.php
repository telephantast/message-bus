<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Identification\IdGenerator;
use Thesis\MessageBus\Identification\UuidV7Generator;
use Thesis\MessageBus\Internal\MessageMetadataRegistry;
use Thesis\MessageBus\Internal\OutboundEnvelopeFactory;
use Thesis\MessageBus\Metadata\AttributeCommandRouter;
use Thesis\MessageBus\Metadata\AttributeMessageClassifier;
use Thesis\MessageBus\Metadata\AttributeMessageTypeResolver;
use Thesis\MessageBus\Metadata\ClassBasedMessageTypeResolver;
use Thesis\MessageBus\Metadata\CommandRouter;
use Thesis\MessageBus\Metadata\CommandRouters;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageClassifiers;
use Thesis\MessageBus\Metadata\MessageTypeResolver;
use Thesis\MessageBus\Metadata\MessageTypeResolvers;
use Thesis\MessageBus\Serialization\Serializer;
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
     * @param list<MessageTypeResolver> $messageTypeResolvers
     */
    public static function build(
        string $name,
        Dispatcher $dispatcher,
        Serializer $serializer,
        array $commandRouters = [new AttributeCommandRouter()],
        array $messageClassifiers = [new AttributeMessageClassifier()],
        array $messageTypeResolvers = [new AttributeMessageTypeResolver(), new ClassBasedMessageTypeResolver()],
        IdGenerator $idGenerator = new UuidV7Generator(),
    ): self {
        return new self(
            name: $name,
            dispatcher: $dispatcher,
            outboundEnvelopeFactory: new OutboundEnvelopeFactory(
                messageMetadataRegistry: new MessageMetadataRegistry(
                    classifier: new MessageClassifiers($messageClassifiers),
                    typeResolver: new MessageTypeResolvers($messageTypeResolvers),
                    commandRouter: new CommandRouters($commandRouters),
                ),
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
}
