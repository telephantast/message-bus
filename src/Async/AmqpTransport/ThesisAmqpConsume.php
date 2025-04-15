<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Amqp\Client;
use Thesis\Amqp\DeliveryMessage;
use Thesis\Amqp\Message;
use Thesis\MessageBus\Async\Consumer;
use Thesis\MessageBus\Async\EncodedMessage;
use Thesis\MessageBus\Async\MessageDecoder;
use Thesis\MessageBus\Async\TransportConsume;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Time\TimeSpan;

/**
 * @api
 */
final readonly class ThesisAmqpConsume implements TransportConsume
{
    /**
     * @var callable(Message): non-empty-string
     */
    private mixed $onMessageIdMissing;

    /**
     * @param non-negative-int $prefetchCount
     * @param ?callable(Message): non-empty-string $onMessageIdMissing
     */
    public function __construct(
        private Client $client,
        private int $prefetchCount = 1,
        private MessageDecoder $messageDecoder = new MessageDecoder(),
        mixed $onMessageIdMissing = null,
    ) {
        $this->onMessageIdMissing = $onMessageIdMissing
            ?? static fn(): never => throw new \RuntimeException('No message id');
    }

    /**
     * @throws \Throwable
     */
    public function consume(Consumer $consumer): \Closure
    {
        $channel = $this->client->channel();
        $channel->qos(prefetchCount: $this->prefetchCount);

        $consumerTag = $channel->consume(
            callback: function (DeliveryMessage $deliveryMessage) use ($consumer): void {
                $consumer->consume($this->createEnvelope($deliveryMessage->message));
                $deliveryMessage->ack();
            },
            queue: $consumer->queue,
        );

        return static function () use ($channel, $consumerTag): void {
            $channel->cancel($consumerTag);
            $channel->close();
        };
    }

    private function createEnvelope(Message $message): Envelope
    {
        $headers = $message->headers;

        $causationId = self::toNullOrNonEmptyString($headers[ThesisAmqpPublish::CAUSATION_ID_HEADER] ?? null);
        unset($headers[ThesisAmqpPublish::CAUSATION_ID_HEADER]);

        return new Envelope(
            message: $this->messageDecoder->decodeMessage(
                new EncodedMessage(
                    type: $message->type ?? '',
                    contentType: $message->contentType,
                    body: $message->body,
                ),
            ),
            messageId: self::toNullOrNonEmptyString($message->messageId) ?? ($this->onMessageIdMissing)($message),
            causationId: $causationId,
            correlationId: self::toNullOrNonEmptyString($message->correlationId),
            timestamp: $message->timestamp,
            headers: $headers,
            transportOptions: new AmqpOptions(
                deliveryMode: $message->deliveryMode,
                expiration: TimeSpan::fromMilliseconds((int) $message->expiration),
                priority: $message->priority,
            ),
        );
    }

    /**
     * @return ?non-empty-string
     */
    private static function toNullOrNonEmptyString(mixed $value): ?string
    {
        if (\is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
