<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\Metadata\Kind;
use Thesis\Pgmq\Message;
use Thesis\Pgmq\SendMessage;

/**
 * @api
 *
 * @phpstan-type SerializedMetadata = array{
 *     id: non-empty-string,
 *     conversationId: non-empty-string,
 *     causeId: ?non-empty-string,
 *     replyCorrelationId: ?non-empty-string,
 *     kind: string,
 *     origin: non-empty-string,
 *     createdAt: string,
 * }
 */
final readonly class NativeSerializer implements Serializer
{
    public function serialize(Envelope $envelope): SendMessage
    {
        $metadata = $envelope->metadata;

        return new SendMessage(
            valueJson: json_encode(serialize($envelope->payload), JSON_THROW_ON_ERROR),
            headerJson: json_encode([
                'id' => $metadata->id,
                'conversationId' => $metadata->conversationId,
                'causeId' => $metadata->causeId,
                'replyCorrelationId' => $metadata->replyCorrelationId,
                'kind' => $metadata->kind->value,
                'origin' => $metadata->origin,
                'createdAt' => $metadata->createdAt->format(\DateTimeInterface::ATOM),
            ], JSON_THROW_ON_ERROR),
        );
    }

    public function deserialize(Message $message): Envelope
    {
        $serialized = json_decode($message->value, flags: JSON_THROW_ON_ERROR);
        \assert(\is_string($serialized));
        $payload = unserialize($serialized);
        \assert(\is_object($payload));

        /** @var SerializedMetadata */
        $headers = json_decode($message->headers ?? throw new \UnexpectedValueException('Missing headers'), true, flags: JSON_THROW_ON_ERROR);

        return new Envelope(
            payload: $payload,
            metadata: new Metadata(
                id: $headers['id'],
                conversationId: $headers['conversationId'],
                causeId: $headers['causeId'],
                replyCorrelationId: $headers['replyCorrelationId'],
                kind: Kind::from($headers['kind']),
                origin: $headers['origin'],
                createdAt: new \DateTimeImmutable($headers['createdAt']),
            ),
        );
    }
}
