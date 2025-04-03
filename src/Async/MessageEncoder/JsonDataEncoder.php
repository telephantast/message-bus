<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

/**
 * @api
 */
final readonly class JsonDataEncoder implements DataEncoder, DataDecoder
{
    private const string CONTENT_TYPE = 'application/json';

    public function encodeData(mixed $data): array
    {
        return [self::CONTENT_TYPE, json_encode($data, flags: JSON_THROW_ON_ERROR)];
    }

    public function decodeData(?string $contentType, string $encodedData): mixed
    {
        return json_decode($encodedData, associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
