<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface TransportConsume
{
    public function runConsumer(Consumer $consumer): void;

    public function stopConsumer(Consumer $consumer): void;
}
