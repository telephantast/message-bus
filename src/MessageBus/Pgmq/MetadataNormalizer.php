<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

use Thesis\MessageBus\Envelope\Metadata;

/**
 * @api
 */
interface MetadataNormalizer
{
    public function normalizeMetadata(Metadata $metadata): mixed;

    public function denormalizeMetadata(mixed $data): Metadata;
}
