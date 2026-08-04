<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Serialization;

/**
 * @api
 */
final readonly class SplitSerializer implements Serializer, Deserializer
{
    public function __construct(
        private Serializer $serializer,
        private Deserializer $deserializer,
    ) {}

    public function serialize(object $message): SerializedMessage
    {
        return $this->serializer->serialize($message);
    }

    public function deserialize(SerializedMessage $serializedMessage, string $messageClass): object
    {
        return $this->deserializer->deserialize($serializedMessage, $messageClass);
    }
}
