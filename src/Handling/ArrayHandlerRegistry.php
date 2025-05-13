<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\Mapping\CallableHandlerDescriptor;

/**
 * @api
 * @template TWrappedTransaction of object = object
 * @implements HandlerRegistry<TWrappedTransaction>
 */
final class ArrayHandlerRegistry implements HandlerRegistry
{
    /**
     * @template TMethodWrappedTransaction of object
     * @param ?class-string<TMethodWrappedTransaction> $wrappedTransactionClass
     * @return self<TMethodWrappedTransaction>
     */
    public static function create(?string $wrappedTransactionClass = null): self
    {
        /** @var self<TMethodWrappedTransaction> */
        return new self();
    }

    /**
     * @param array<class-string<Message>, list<Handler<*, TWrappedTransaction>>> $handlers
     */
    public function __construct(
        private array $handlers = [],
    ) {}

    public array $messages {
        get => array_keys($this->handlers);
    }

    /**
     * @template TWithMessage of Message
     * @param class-string<TWithMessage> $message
     * @param Handler<TWithMessage, TWrappedTransaction> $handler
     */
    public function with(string $message, Handler $handler): static
    {
        $registry = clone $this;
        $registry->handlers[$message][] = $handler;

        return $registry;
    }

    /**
     * @template TWithMessage of Message
     * @param callable(TWithMessage, HandleContext<TWrappedTransaction>, Envelope<TWithMessage>): void $handler
     */
    public function withCallableHandler(callable $handler): static
    {
        $descriptor = CallableHandlerDescriptor::fromFunction(new \ReflectionFunction($handler(...)));
        $handler = new CallableHandler($handler, $descriptor->id);
        $registry = clone $this;

        foreach ($descriptor->messages as $message) {
            $registry->handlers[$message][] = $handler;
        }

        return $registry;
    }

    /**
     * @template TMessage of Message
     * @param class-string<TMessage> $messageClass
     * @return list<Handler<TMessage, TWrappedTransaction>>
     */
    public function getHandlers(string $messageClass): array
    {
        /** @var list<Handler<TMessage, TWrappedTransaction>> */
        return $this->handlers[$messageClass] ?? [];
    }
}
