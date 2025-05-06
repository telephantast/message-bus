<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

/**
 * @api
 */
final readonly class JsonEncoder implements Encoder
{
    public function encode(mixed $data): EncodedData
    {
        return new EncodedData(
            data: json_encode($data, flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            type: 'application/json',
        );
    }

    public function decode(EncodedData $encodedData): mixed
    {
        return json_decode($encodedData->data, associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
