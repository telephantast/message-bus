<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Serialization;

/**
 * @api
 */
interface Deserializer
{
    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return T
     * @throws MessageDeserializationFailed
     */
    public function deserialize(SerializedMessage $serializedMessage, string $messageClass): object;
}
