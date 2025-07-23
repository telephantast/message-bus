<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface ConsumerTransport extends ProducerTransport
{
    /**
     * @param non-empty-string $queue
     * @param callable(non-empty-list<Envelope>): void $consumer
     */
    public function runConsumer(string $queue, callable $consumer): Run;
}
