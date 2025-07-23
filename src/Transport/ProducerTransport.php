<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface ProducerTransport
{
    /**
     * @param non-empty-string $queue
     * @param non-empty-list<Envelope> $commands
     */
    public function send(string $queue, array $commands): void;
}
