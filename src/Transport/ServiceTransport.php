<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface ServiceTransport
{
    /**
     * @param non-empty-string $service
     * @param callable(Envelope): mixed $handler
     */
    public function runService(string $service, callable $handler): Run;
}
