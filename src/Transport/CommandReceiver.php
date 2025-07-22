<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface CommandReceiver
{
    /**
     * @param non-empty-string $queue
     * @param callable(non-empty-list<Envelope>): void $consumer
     * @param positive-int $maxBatchSize
     */
    public function startQueue(string $queue, callable $consumer, int $maxBatchSize = 1): Run;
}
