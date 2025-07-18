<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface CommandReceiver
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope): void $handler
     */
    public function startCommandConsumer(string $endpoint, callable $handler): Canceller;
}
