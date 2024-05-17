<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
final readonly class ObjectSerializer implements ObjectNormalizer, ObjectDenormalizer
{
    public function normalize(object $object): mixed
    {
        return serialize($object);
    }

    public function denormalize(mixed $data, string $class): object
    {
        \assert(\is_string($data));
        $object = unserialize($data);
        \assert($object instanceof $class);

        return $object;
    }
}
