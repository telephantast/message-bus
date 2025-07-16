<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Stamps;

/**
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TMessage>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @param non-empty-list<class-string<TMessage>> $messageClasses
     * @param callable(TMessage, Context, Stamps): (TResult|Result<TResult>) $handler
     */
    public function __construct(
        public array $messageClasses,
        private mixed $handler,
    ) {}

    public function handle(Envelope $envelope, Context $context): mixed
    {
        $result = ($this->handler)($envelope->message, $context, $envelope->stamps);

        if ($result instanceof Result) {
            $context->send(...$result->commands);
            $context->publish(...$result->events);

            return $result->result;
        }

        /** @phpstan-ignore return.type */
        return $result;
    }
}
