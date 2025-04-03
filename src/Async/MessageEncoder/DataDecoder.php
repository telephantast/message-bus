<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

/**
 * @api
 */
interface DataDecoder
{
    public function decodeData(?string $contentType, string $encodedData): mixed;
}
