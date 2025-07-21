<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;

interface Client
{
    /**
     * @template TResult
     * @param non-empty-string $service
     * @return ($call is Envelope<Call<TResult>> ? TResult : mixed)
     */
    public function invoke(string $service, Envelope $call): mixed;
}
