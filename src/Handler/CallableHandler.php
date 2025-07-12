<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Publisher;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Sender;
use Thesis\MessageBus\Stamps;

/**
 * @template-covariant TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TMessage>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @param list<class-string<TMessage>> $messageClasses
     * @param callable(TMessage, Context, Stamps): (TResult|Result<TResult>) $handler
     */
    public function __construct(
        public array $messageClasses,
        private mixed $handler,
    ) {}

    public function handle(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        $result = ($this->handler)($envelope->message, $context, $envelope->stamps);

        if ($result instanceof Result) {
            $context->get(Sender::class)->send(...$result->commands);
            $context->get(Publisher::class)->publish(...$result->events);

            return $result->result;
        }

        /** @phpstan-ignore return.type */
        return $result;
    }
}
