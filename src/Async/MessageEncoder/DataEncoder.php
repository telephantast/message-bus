<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

/**
 * @api
 */
interface DataEncoder
{
    /**
     * @return array{string, string} content-type and encoded data
     */
    public function encodeData(mixed $data): array;
}
