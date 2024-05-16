<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
interface TransportConsume
{
    /**
     * @param non-empty-string $queue
     * @param callable(Envelope): void $consumer
     */
    public function consume(string $queue, callable $consumer): void;

    public function disconnect(): void;
}
