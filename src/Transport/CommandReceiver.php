<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface CommandReceiver
{
    /**
     * @param non-empty-string $queue
     * @param callable(Envelope): void $handler
     */
    public function startQueue(string $queue, callable $handler): Run;
}
