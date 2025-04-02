<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

/**
 * @api
 */
interface TransportConsume
{
    /**
     * @return \Closure(): void the cancel function
     */
    public function runConsumer(Consumer $consumer): \Closure;
}
