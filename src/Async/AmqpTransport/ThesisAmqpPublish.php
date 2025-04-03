<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Amqp\Channel;
use Thesis\Amqp\Client;
use Thesis\Amqp\Confirmation;
use Thesis\Amqp\Message as AmqpMessage;
use Thesis\Amqp\PublishResult;
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

        $confirmations = [];

        foreach ($envelopes as $envelope) {
            $transportOptions = $envelope->transportOptions ?? new AmqpOptions();

            if (!$transportOptions instanceof AmqpOptions) {
                throw new UnsupportedTransportOptions(
                    transport: self::class,
                    received: $transportOptions::class,
                    supported: [AmqpOptions::class],
                );
            }

            $confirmation = $this->channel->publish(
                message: $this->createAmqpMessageFromEnvelope($envelope, $transportOptions),
                exchange: $this->routingTopology->resolveExchange($envelope->message::class),
                immediate: $transportOptions->immediate,
            );
            \assert($confirmation !== null);
            $confirmations[] = $confirmation;
        }

        foreach (Confirmation::awaitAll($confirmations) as $publishResult) {
            if ($publishResult !== PublishResult::Acked) {
                throw new \LogicException('Failed to publish an envelope');
            }
        }
    }

    private function createAmqpMessageFromEnvelope(Envelope $envelope, AmqpOptions $options): AmqpMessage
    {
        $encodedMessage = $this->messageEncoder->encodeMessage($envelope->message);

        return new AmqpMessage(
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
