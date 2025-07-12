<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;

/**
 * @template TMessage of Message = Event
 * @implements Handler<TMessage>
 */
final class Handlers implements Handler
{
    /**
     * @var array<class-string<Message<*>>, non-empty-list<Handler<*>>>
     */
    public private(set) array $handlers = [];

    /**
     * @template TWithMessage of Message
     * @param Handler<TWithMessage> $handler
     * @return self<TMessage|TWithMessage>
     */
    public function with(Handler $handler): self
    {
        $copy = clone $this;

        foreach ($handler->messageClasses as $messageClass) {
            if (!is_a($messageClass, Event::class, allow_string: true) && isset($copy->handlers[$messageClass])) {
                throw new \LogicException(\sprintf(
                    'Non event message `%s` already has a handler',
                    $messageClass,
                ));
            }

            $copy->handlers[$messageClass][] = $handler;
        }

        return $copy;
    }

    public array $messageClasses { get => array_keys($this->handlers); }

    public function handle(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        $handlers = $this->handlers[$envelope->messageClass] ?? [];

        if (!$envelope->message instanceof Event) {
            if (\count($handlers) !== 1) {
                throw new \LogicException(\sprintf(
                    'No handler for non-event message `%s`',
                    $envelope->messageClass,
                ));
            }

            /** @phpstan-ignore argument.type */
            return $handlers[0]->handle($endpoint, $envelope, $context);
        }

        foreach ($this->handlers[$envelope->messageClass] ?? [] as $handler) {
            /** @phpstan-ignore argument.type */
            $handler->handle($endpoint, $envelope, $context);
        }

        /** @phpstan-ignore return.type */
        return null;
    }
}
