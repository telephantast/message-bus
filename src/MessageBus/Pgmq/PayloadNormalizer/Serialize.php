<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq\PayloadNormalizer;

use Thesis\MessageBus\Pgmq\PayloadNormalizer;

final readonly class Serialize implements PayloadNormalizer
{
    public function normalizePayload(object $payload): string
    {
        return serialize($payload);
    }

    public function denormalizePayload(string $class, mixed $data): object
    {
        \assert(\is_string($data));

        $unserialized = unserialize($data);

        \assert($unserialized instanceof $class);

        return $unserialized;
    }
}
