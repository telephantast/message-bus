<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;

/**
 * @template-contravariant TMessage of Message = Event
 * @implements Handler<TMessage>
 */
final class Handlers implements Handler
{
    /**
     * @var array<class-string<Message<*>>, non-empty-list<Handler<*>>>
     */
    private(set) public array $handlers = [];

    /**
     * @template TWithMessage of Message
     * @param non-empty-list<class-string<TWithMessage>> $messages
     * @param Handler<TWithMessage> $handler
     * @return self<TMessage|TWithMessage>
     */
    public function with(array $messages, Handler $handler): self
    {
        $copy = clone $this;

        foreach ($messages as $message) {
            if (!is_a($message, Event::class, allow_string: true) && isset($copy->handlers[$message])) {
                throw new \LogicException(\sprintf(
                    'Non event message `%s` already has a handler',
                    $message,
                ));
            }

            $copy->handlers[$message][] = $handler;
        }

        return $copy;
    }

    public function handle(Envelope $envelope, Context $context): Result
    {
        $handlers = $this->handlers[$envelope->messageClass] ?? [];

        if (!$envelope->message instanceof Event) {
            if (\count($handlers) !== 1) {
                throw new \LogicException(\sprintf(
                    'No handler for non-event message `%s`',
                    $envelope->messageClass,
                ));
            }

            /** @phpstan-ignore argument.type, return.type */
            return $handlers[0]->handle($envelope, $context);
        }

        $result = new Result();

        foreach ($this->handlers[$envelope->messageClass] ?? [] as $handler) {
            /** @phpstan-ignore argument.type */
            $result = $result->merge($handler->handle($envelope, $context));
        }

        /** @phpstan-ignore return.type */
        return $result;
    }
}
