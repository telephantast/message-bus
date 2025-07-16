<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Call;
use Thesis\MessageBus\Envelope;

interface CallClient
{
    /**
     * @template TResult
     * @param non-empty-string $endpoint
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function invoke(string $endpoint, Envelope $call): mixed;
}
