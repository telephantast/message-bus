<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Amqp\Channel;
use Thesis\Amqp\Client;
use Thesis\Amqp\Message;
use Thesis\Amqp\PublishMessage;
use Thesis\MessageBus\Async\MessageEncoder;
use Thesis\MessageBus\Async\TransportPublish;
use Thesis\MessageBus\Async\UnsupportedTransportOptions;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final class ThesisAmqpPublish implements TransportPublish
{
    public const string CAUSATION_ID_HEADER = 'X-Causation-ID';

    private ?Channel $channel = null;

    public function __construct(
        private readonly Client $client,
        private readonly RoutingTopology $routingTopology = new ConventionalRoutingTopology(),
        private readonly MessageEncoder $messageEncoder = new MessageEncoder(),
    ) {}

    /**
     * @throws \Throwable
     */
    public function publish(array $envelopes): void
    {
        if ($this->channel === null) {
            $this->channel = $this->client->channel();
            $this->channel->confirmSelect();
        }

        $confirmation = $this->channel->publishBatch(array_map(
            fn(Envelope $envelope): PublishMessage => new PublishMessage(
                message: $this->createMessage($envelope),
                exchange: $this->routingTopology->resolveExchange($envelope->message::class),
            ),
            $envelopes,
        ));

        if ($confirmation->unconfirmed() !== []) {
            throw new \RuntimeException('Failed to publish');
        }
    }

    private function createMessage(Envelope $envelope): Message
    {
        $options = $envelope->transportOptions ?? new AmqpOptions();

        if (!$options instanceof AmqpOptions) {
            throw new UnsupportedTransportOptions(
                transport: self::class,
                received: $options::class,
                supported: [AmqpOptions::class],
            );
        }

        $encodedMessage = $this->messageEncoder->encodeMessage($envelope->message);

        return new Message(
            body: $encodedMessage->body,
            headers: [
                ...$envelope->headers,
                self::CAUSATION_ID_HEADER => $envelope->causationId,
            ],
            contentType: $encodedMessage->contentType,
            deliveryMode: $options->deliveryMode,
            priority: $options->priority,
            correlationId: $envelope->correlationId,
            expiration: $options->expiration === null ? null : (string) $options->expiration->toMilliseconds(),
            messageId: $envelope->messageId,
            timestamp: $envelope->timestamp,
            type: $encodedMessage->type,
        );
    }
}
