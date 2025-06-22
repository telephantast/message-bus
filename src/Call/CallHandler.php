<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Call;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;

/**
 * @template-contravariant TCall of Call
 */
interface CallHandler
{
    /**
     * @template TResult
     * @param Envelope<TCall&Call<TResult>> $call
     * @param Invoker<Call> $invoker
     * @return Result<TResult>
     */
    public function handle(Envelope $call, Invoker $invoker): Result;
}
