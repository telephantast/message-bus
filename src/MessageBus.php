<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Message;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessors;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\Endpoint;
use Thesis\MessageBus\Tracing\AddCauseIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddConversationIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddMessageIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddTimestampToOutgoingEnvelope;

/**
 * @implements Invoker<Call>
 */
final readonly class MessageBus implements Sender, Invoker
{
    /**
     * @param array<non-empty-string, EndpointConfig> $endpointConfigs
     * @param list<OutgoingEnvelopeProcessor> $outgoingEnvelopeProcessors
     * @param non-empty-string $messageBusEndpointName
     */
    public static function build(
        array $endpointConfigs = [],
        array $outgoingEnvelopeProcessors = [
            new AddTimestampToOutgoingEnvelope(),
            new AddMessageIdToOutgoingEnvelope(),
            new AddConversationIdToOutgoingEnvelope(),
            new AddCauseIdToOutgoingEnvelope(),
        ],
        string $messageBusEndpointName = 'message_bus',
    ): self {
        $endpoints = [];

        foreach ($endpointConfigs as $name => $endpointConfig) {
            $endpoints[$name] = new Endpoint(
                name: $name,
                handler: $endpointConfig->handler,
                handlesCommand: $endpointConfig->handlesCommand,
                publishesEvent: $endpointConfig->publishesEvent,
                handlesCall: $endpointConfig->handlesCall,
                storage: $endpointConfig->storage,
                outgoingEnvelopeProcessor: $endpointConfig->outgoingEnvelopeProcessor,
                commandSender: $endpointConfig->commandSender,
                commandReceiver: $endpointConfig->commandReceiver,
                eventPublisher: $endpointConfig->eventPublisher,
                eventReceiver: $endpointConfig->eventReceiver,
            );
        }

        return new self(
            endpoints: $endpoints,
            dispatcher: new Dispatcher($endpoints),
            outgoingEnvelopeProcessor: new OutgoingEnvelopeProcessors($outgoingEnvelopeProcessors),
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
        private OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        private string $name,
    ) {}

    public function setup(): void
    {
        foreach ($this->endpoints as $endpoint) {
            $endpoint->setup($this->dispatcher);
        }
    }

    /**
     * @no-named-arguments
     * @param Command|Envelope<Command> ...$commands
     */
    public function send(Command|Envelope ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->dispatcher->dispatchCommands(array_map($this->prepareMessage(...), $commands));
    }

    /**
     * @template TResult
     * @param Call<TResult>|Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function invoke(Call|Envelope $call): mixed
    {
        return $this->dispatcher->dispatchCall($this->prepareMessage($call));
    }

    /**
     * @no-named-arguments
     * @param non-empty-string ...$endpoints
     */
    public function run(string ...$endpoints): void
    {
        foreach ($endpoints as $name) {
            $this->endpoint($name)->run($this->dispatcher);
        }
    }

    /**
     * @param non-empty-string $name
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new \LogicException();
    }

    /**
     * @template TMessage of Message
     * @param TMessage|Envelope<TMessage> $envelope
     * @return Envelope<TMessage>
     */
    private function prepareMessage(Message|Envelope $envelope): Envelope
    {
        return $this->outgoingEnvelopeProcessor->process($this->name, Envelope::wrap($envelope));
    }
}
