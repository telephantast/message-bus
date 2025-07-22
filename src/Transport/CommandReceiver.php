<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface CommandReceiver
{
    /**
     * @param non-empty-string $queue
     * @param callable(non-empty-list<Envelope>): void $consumer
     */
    public function startQueue(string $queue, callable $consumer): Run;
}
