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
final readonly class ResultCallableHandler implements Handler
{
    /**
     * @param non-empty-list<class-string<TMessage>> $messageClasses
     * @param callable(TMessage, Context, Stamps): Result<TResult> $handler
     */
    public function __construct(
        public array $messageClasses,
        private mixed $handler,
    ) {}

    public function handle(Envelope $envelope, Context $context): mixed
    {
        $result = ($this->handler)($envelope->message, $context, $envelope->stamps);
        $context->send(...$result->commands);
        $context->publish(...$result->events);

        /** @phpstan-ignore return.type */
        return $result->result;
    }
}
