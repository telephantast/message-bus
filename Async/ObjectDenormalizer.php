<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface ObjectDenormalizer
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function denormalize(mixed $data, string $class): object;
}
