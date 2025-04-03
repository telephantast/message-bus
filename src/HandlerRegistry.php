<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\HandlerRegistry\HandlerNotFound;
use Thesis\MessageBus\HandlerRegistry\HandlerRegistryBuilder;

/**
 * @api
 */
abstract class HandlerRegistry
{
    final public static function builder(): HandlerRegistryBuilder
    {
        return new HandlerRegistryBuilder();
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param callable(TMessage, Context<TResult, TMessage>): TResult $handler
     * @param list<Middleware> $middleware
     */
    final public static function oneCallableHandler(callable $handler, array $middleware = []): self
    {
        return (new HandlerRegistryBuilder())
            ->addCallableHandler($handler, $middleware)
            ->build();
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return Handler<TResult, TMessage>
     */
    final public function get(string $messageClass): Handler
    {
        $handler = $this->find($messageClass);

        if ($handler !== null) {
            return $handler;
        }

        if (is_subclass_of($messageClass, Event::class)) {
            /** @var CallableHandler<TResult, TMessage> */
            return new CallableHandler(static fn(): null => null);
        }

        throw new HandlerNotFound(\sprintf('No handler for non-event message %s', $messageClass));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     */
    abstract public function find(string $messageClass): ?Handler;
}
