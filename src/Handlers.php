<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template TSupportedMessages of Message = never
 * @template-covariant TRequiredMessages of Message = Event
 * @template-contravariant TTransaction of object = object
 * @implements Handler<TSupportedMessages, TRequiredMessages, TTransaction>
 */
final class Handlers implements Handler
{
    /**
     * @template TForTransaction of object
     * @param class-string<TForTransaction> $transactionClass
     * @return self<never, Event, TForTransaction>
     */
    public static function forTransaction(string $transactionClass): self
    {
        /** @var self<never, Event, TForTransaction> */
        return new self();
    }

    public string $id { get => implode(', ', array_column($this->handlers, 'id')); }

    public array $messageClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string<TSupportedMessages>, non-empty-list<Handler<Message>>>
     */
    private array $handlers = [];

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @template THandlerRequiredMessages of Message = never
     * @template THandlerTransaction of object = object
     * @param (\Closure(TMessage, HandlerContext<THandlerRequiredMessages, THandlerTransaction>, Stamps): TResult)|Handler<TMessage, THandlerRequiredMessages, THandlerTransaction> $handler
     * @return self<TSupportedMessages|TMessage, TRequiredMessages|THandlerRequiredMessages, TTransaction&THandlerTransaction>
     */
    public function with(\Closure|Handler $handler): self
    {
        $copy = clone $this;

        if ($handler instanceof \Closure) {
            $handler = new CallableHandler($handler);
        }

        foreach ($handler->messageClasses as $messageClass) {
            if (!is_a($messageClass, Event::class, allow_string: true) && isset($copy->handlers[$messageClass])) {
                throw new \Exception(\sprintf(
                    'Non-event message `%s` already has a handler `%s`.',
                    $messageClass,
                    $copy->handlers[$messageClass][0]->id,
                ));
            }

            /** @phpstan-ignore assign.propertyType */
            $copy->handlers[$messageClass][] = $handler;
        }

        return $copy;
    }

    public function handle(Envelope $envelope, HandlerContext $context): mixed
    {
        $messageClass = $envelope->message::class;
        $handlers = $this->handlers[$messageClass] ?? [];

        if ($envelope->message instanceof Event) {
            foreach ($handlers as $handler) {
                /** @phpstan-ignore argument.type */
                $handler->handle($envelope, $context);
            }

            /** @phpstan-ignore return.type */
            return null;
        }

        if ($handlers === []) {
            throw new \Exception(\sprintf(
                'Non-event message `%s` does not have a handler',
                $messageClass,
            ));
        }

        /** @phpstan-ignore argument.type */
        return $handlers[0]->handle($envelope, $context);
    }
}
