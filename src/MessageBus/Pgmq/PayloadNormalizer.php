<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

/**
 * @api
 */
interface PayloadNormalizer
{
    public function normalizePayload(object $payload): mixed;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function denormalizePayload(string $class, mixed $data): object;
}
