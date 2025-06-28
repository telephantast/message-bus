<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;

interface CallServer
{
    /**
     * @template TResult
     * @param non-empty-string $endpoint
     * @param callable(Envelope<Call<TResult>>): TResult $handler
     */
    public function serve(string $endpoint, callable $handler): void;
}
