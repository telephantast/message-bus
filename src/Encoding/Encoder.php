<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

/**
 * @api
 */
interface Encoder
{
    public function encode(mixed $data): EncodedData;

    public function decode(EncodedData $encodedData): mixed;
}
