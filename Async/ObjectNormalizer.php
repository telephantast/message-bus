<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface ObjectNormalizer
{
    public function normalize(object $object): mixed;
}
