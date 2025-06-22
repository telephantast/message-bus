<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Call;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Stamps;

/**
 * @template-covariant TResult
 * @template-contravariant TCall of Call<TResult>
 * @implements CallHandler<TCall>
 */
final readonly class CallableCallHandler implements CallHandler
{
    /**
     * @param callable(TCall, Invoker<Call>, Stamps): (TResult|Result<TResult>) $handler
     */
    public function __construct(
        private mixed $handler,
    ) {}

    public function handle(Envelope $call, Invoker $invoker): Result
    {
        $result = ($this->handler)($call->message, $invoker, $call->stamps);

        if ($result instanceof Result) {
            /** @phpstan-ignore return.type */
            return $result;
        }

        /** @phpstan-ignore return.type */
        return new Result($result);
    }
}
