<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Message;
use Thesis\MessageBus\Envelope\EnvelopeProcessor;
use Thesis\MessageBus\Envelope\MessageIdGenerator;
use Thesis\MessageBus\Envelope\RandomMessageIdGenerator;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\Endpoint;
use Thesis\MessageBus\Internal\EnvelopeFactory;

/**
 * @implements Invoker<Call>
 */
final readonly class MessageBus implements Sender, Invoker
{
    /**
     * @param array<non-empty-string, EndpointConfig> $endpointConfigs
     * @param list<EnvelopeProcessor> $outgoingEnvelopeProcessors
     * @param non-empty-string $messageBusEndpointName
     */
    public static function build(
        array $endpointConfigs = [],
        array $outgoingEnvelopeProcessors = [],
        MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
        ?ClockInterface $clock = null,
        string $messageBusEndpointName = 'message_bus',
    ): self {
        $envelopeFactory = new EnvelopeFactory(
            processors: $outgoingEnvelopeProcessors,
            messageIdGenerator: $messageIdGenerator,
            clock: $clock,
        );
        $endpoints = [];

        foreach ($endpointConfigs as $name => $endpointConfig) {
            $endpoints[$name] = new Endpoint(
                name: $name,
                handler: $endpointConfig->handler,
                handlesCommand: $endpointConfig->handlesCommand,
                publishesEvent: $endpointConfig->publishesEvent,
                handlesCall: $endpointConfig->handlesCall,
                storage: $endpointConfig->storage,
                envelopeFactory: $envelopeFactory,
                commandSender: $endpointConfig->commandSender,
                commandReceiver: $endpointConfig->commandReceiver,
                eventPublisher: $endpointConfig->eventPublisher,
                eventReceiver: $endpointConfig->eventReceiver,
                callClient: $endpointConfig->callClient,
                callServer: $endpointConfig->callServer,
            );
        }

        return new self(
            endpoints: $endpoints,
            dispatcher: new Dispatcher($endpoints),
            envelopeFactory: $envelopeFactory,
            name: $messageBusEndpointName,
        );
    }

    /**
     * @param array<non-empty-string, Endpoint> $endpoints
     * @param non-empty-string $name
     */
    private function __construct(
        private array $endpoints,
        private Dispatcher $dispatcher,
        private EnvelopeFactory $envelopeFactory,
        private string $name,
    ) {}

    public function setup(): void
    {
        foreach ($this->endpoints as $endpoint) {
            $endpoint->setup($this->dispatcher);
        }
    }

    public function send(Command|Envelope ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->dispatcher->dispatchCommands(array_map($this->createEnvelope(...), $commands));
    }

    public function invoke(Call|Envelope $call): mixed
    {
        return $this->dispatcher->dispatchCall($this->createEnvelope($call));
    }

    /**
     * @template TMessage of Message
     * @param TMessage|Envelope<TMessage> $message
     * @return Envelope<TMessage>
     */
    private function createEnvelope(Message|Envelope $message): Envelope
    {
        return $this->envelopeFactory->create($this->name, $message);
    }

    /**
     * @param array<non-empty-string, list<Run>> $selector
     */
    public function run(array $selector = []): void
    {
        if ($selector === []) {
            $selector = array_fill_keys(array_keys($this->endpoints), []);
        }

        foreach ($selector as $endpoint => $runs) {
            $this->endpoint($endpoint)->run($this->dispatcher, $runs);
        }
    }

    /**
     * @param non-empty-string $name
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new \LogicException();
    }
}
