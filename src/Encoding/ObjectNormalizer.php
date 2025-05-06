<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

/**
 * @api
 */
interface ObjectNormalizer
{
    public function normalizeObject(object $object): mixed;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function denormalizeObject(string $class, mixed $data): object;
}
