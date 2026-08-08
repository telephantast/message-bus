<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
interface Deserializer
{
    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return T
     * @throws DeserializationFailed
     */
    public function deserialize(SerializedMessage $serializedMessage, string $messageClass): object;
}
