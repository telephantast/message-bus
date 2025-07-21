<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface Server
{
    /**
     * @param non-empty-string $service
     * @param callable(Envelope): mixed $handler
     */
    public function runServer(string $service, callable $handler): Run;
}
