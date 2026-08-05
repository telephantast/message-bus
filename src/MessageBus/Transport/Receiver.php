<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface Receiver
{
    /**
     * @param non-empty-string $queue
     */
    public function startConsumer(string $queue, ConsumerHandler $handler): Consumer;
}
