<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface TransportConsume
{
    /**
     * @return \Closure(): void the cancel function
     */
    public function runConsumer(Consumer $consumer): \Closure;

    public function disconnect(): void;
}
