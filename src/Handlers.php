<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = Event
 * @template TTransaction of object = object
 */
final class Handlers
{
    /**
     * @template TForTransaction of object
     * @param class-string<TForTransaction> $transactionClass
     * @return self<Event, TForTransaction>
     */
    public static function forTransaction(string $transactionClass): self
    {
        /** @var self<Event, TForTransaction> */
        return new self();
    }

    /**
     * @var list<class-string<Message>>
     */
    public array $messages { get => array_keys($this->handlers); }

    /**
     * @var array<class-string<Message>, non-empty-list<Handler>>
     */
    private array $handlers = [];

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Handler<TResult, TMessage, Message, TTransaction> $handler
     * @return self<TSupportedMessages|TMessage, TTransaction>
     */
    public function with(Handler $handler): self
    {
        $copy = clone $this;

        foreach ($handler->messageClasses as $messageClass) {
            if (!is_a($messageClass, Event::class, allow_string: true) && isset($copy->handlers[$messageClass])) {
                throw new \Exception(\sprintf(
                    'Non-event message `%s` already has a handler `%s`.',
                    $messageClass,
                    $copy->handlers[$messageClass][0]->id,
                ));
            }

            $copy->handlers[$messageClass][] = $handler;
        }

        return $copy;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param callable(TMessage, HandlerContext<Message, TTransaction>): TResult $handler
     * @param ?non-empty-string $id
     * @return self<TSupportedMessages|TMessage, TTransaction>
     */
    public function withCallable(callable $handler, ?string $id = null): self
    {
        return $this->with(new CallableHandler($handler, $id));
    }

    /**
     * @template TResult
     * @param TSupportedMessages&Message<TResult> $message
     * @param HandlerContext<never, TTransaction> $context
     * @return TResult
     */
    public function handle(Message $message, HandlerContext $context): mixed
    {
        /** @var list<Handler<TResult, Message<TResult>, never, TTransaction>> */
        $handlers = $this->handlers[$message::class] ?? [];

        if ($message instanceof Event) {
            foreach ($handlers as $handler) {
                $handler->handle($message, $context);
            }

            /** @phpstan-ignore return.type */
            return null;
        }

        if ($handlers === []) {
            throw new \Exception(\sprintf(
                'Non-event message `%s` does not have a handler',
                $message::class,
            ));
        }

        return $handlers[0]->handle($message, $context);
    }
}
