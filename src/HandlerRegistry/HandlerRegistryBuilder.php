<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\EventHandlers;
use Thesis\MessageBus\Handler\HandlerWithMiddleware;
use Thesis\MessageBus\Handler\Mapping\HandlerDescriptor;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\Middleware;

/**
 * @api
 */
final class HandlerRegistryBuilder
{
    /**
     * @var array<class-string<Message>, Handler>
     */
    private array $handlers = [];

    /**
     * @var array<class-string<Event>, non-empty-list<Handler<null, Event>>>
     */
    private array $eventHandlers = [];

    /**
     * @internal
     * @psalm-internal Thesis\MessageBus
     */
    public function __construct() {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param callable(TMessage, Context<TResult, TMessage>): TResult $handler
     * @param list<Middleware> $middleware
     */
    public function addCallableHandler(callable $handler, array $middleware = []): self
    {
        $descriptor = HandlerDescriptor::fromFunction(new \ReflectionFunction($handler(...)));

        /** @psalm-suppress InvalidArgument */
        return $this->addHandler(
            /** @phpstan-ignore argument.type */
            messageClasses: $descriptor->messageClasses,
            handler: new CallableHandler($handler),
            middleware: $middleware,
        );
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage>|non-empty-list<class-string<TMessage>> $messageClasses
     * @param Handler<TResult, TMessage> $handler
     * @param list<Middleware> $middleware
     */
    public function addHandler(string|array $messageClasses, Handler $handler, array $middleware = []): self
    {
        if (\is_string($messageClasses)) {
            $messageClasses = [$messageClasses];
        }

        if ($middleware !== []) {
            $handler = new HandlerWithMiddleware($handler, $middleware);
        }

        foreach ($messageClasses as $messageClass) {
            if (is_a($messageClass, Event::class, true)) {
                /** @var Handler<null, Event> $handler */
                $this->eventHandlers[$messageClass][] = $handler;
            } else {
                if (isset($this->handlers[$messageClass])) {
                    throw new \LogicException();
                }

                $this->handlers[$messageClass] = $handler;
            }
        }

        return $this;
    }

    public function build(): HandlerRegistry
    {
        /** @psalm-suppress InvalidArgument */
        return new ArrayHandlerRegistry([
            ...$this->handlers,
            ...array_map(
                static fn(array $handlers): Handler => \count($handlers) === 1
                    ? $handlers[0]
                    : new EventHandlers($handlers),
                $this->eventHandlers,
            ),
        ]);
    }
}
