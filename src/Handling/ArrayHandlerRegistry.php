<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\Mapping\CallableHandlerDescriptor;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 * @template TTransaction of Transaction = Transaction
 * @implements HandlerRegistry<TTransaction>
 */
final class ArrayHandlerRegistry implements HandlerRegistry
{
    /**
     * @template TMTransaction of Transaction
     * @param class-string<TMTransaction> $transaction
     * @return self<TMTransaction>
     */
    public static function create(string $transaction = Transaction::class): self
    {
        /** @var self<TMTransaction> */
        return new self();
    }

    /**
     * @param array<class-string<Message>, list<Handler<*, TTransaction>>> $handlers
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
     * @param Handler<TWithMessage, TTransaction> $handler
     */
    public function with(string $message, Handler $handler): static
    {
        $registry = clone $this;
        $registry->handlers[$message][] = $handler;

        return $registry;
    }

    /**
     * @template TWithMessage of Message
     * @param callable(TWithMessage, HandleContext<TTransaction>, Envelope<TWithMessage>): void $handler
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
     * @return list<Handler<TMessage, TTransaction>>
     */
    public function getHandlers(string $messageClass): array
    {
        /** @var list<Handler<TMessage, TTransaction>> */
        return $this->handlers[$messageClass] ?? [];
    }
}
