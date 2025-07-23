<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Method;

interface ClientTransport
{
    /**
     * @template TResult
     * @param non-empty-string $service
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function invoke(string $service, Envelope $method): mixed;
}
