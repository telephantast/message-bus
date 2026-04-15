<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq\MetadataNormalizer;

use Thesis\MessageBus\Envelope\Kind;
use Thesis\MessageBus\Envelope\Metadata;
use Thesis\MessageBus\Pgmq\MetadataNormalizer;

/**
 * @api
 *
 * @phpstan-type Data = array{
 *     class: class-string,
 *     kind: non-empty-string,
 *     source: non-empty-string,
 *     id: non-empty-string,
 *     conversationId: non-empty-string,
 *     causeId: ?non-empty-string,
 * }
 */
final readonly class Basic implements MetadataNormalizer
{
    /**
     * @return Data
     */
    public function normalizeMetadata(Metadata $metadata): array
    {
        return [
            'class' => $metadata->class,
            'kind' => $metadata->kind->value,
            'source' => $metadata->source,
            'id' => $metadata->id,
            'conversationId' => $metadata->conversationId,
            'causeId' => $metadata->causeId,
        ];
    }

    public function denormalizeMetadata(mixed $data): Metadata
    {
        /** @var Data $data */
        return new Metadata(
            class: $data['class'],
            kind: Kind::from($data['kind']),
            source: $data['source'],
            id: $data['id'],
            conversationId: $data['conversationId'],
            causeId: $data['causeId'],
        );
    }
}
