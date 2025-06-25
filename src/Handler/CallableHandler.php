<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Stamps;

/**
 * @template-covariant TResult
 * @template-contravariant TMessage of Message
 * @implements Handler<TMessage>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @param callable(TMessage, Context, Stamps): (TResult|Result<TResult>) $handler
     */
    public function __construct(
        private mixed $handler,
    ) {}

    public function handle(Envelope $envelope, Context $context): Result
    {
        $result = ($this->handler)($envelope->message, $context, $envelope->stamps);

        if ($result instanceof Result) {
            /** @phpstan-ignore return.type */
            return $result;
        }

        /** @phpstan-ignore return.type */
        return new Result($result);
    }
}
