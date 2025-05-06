<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

/**
 * @api
 */
final readonly class PHPSerializeObjectNormalizer implements ObjectNormalizer
{
    public function normalizeObject(object $object): mixed
    {
        return serialize($object);
    }

    public function denormalizeObject(string $class, mixed $data): object
    {
        if (!\is_string($data)) {
            throw new \LogicException(\sprintf('Failed to unserialize data into an object of class `%s`', $class));
        }

        $object = unserialize($data);

        if (!$object instanceof $class) {
            throw new \LogicException(\sprintf('Failed to unserialize data into an object of class `%s`', $class));
        }

        return $object;
    }
}
