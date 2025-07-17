<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler\HandlerReflector;
use Thesis\MessageBus\Handler\MessageClass;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\Handler;
use function Typhoon\Formatter\formatFunction;
use function Typhoon\Formatter\formatReflectedFunction;

final class Handlers
{
    /**
     * @var array<class-string<Command>, Handler<null, Command>>
     */
    private array $commandHandlers = [];

    /**
     * @var array<class-string<Event>, non-empty-list<Handler<null, Event>>>
     */
    private array $eventHandlers = [];

    /**
     * @var list<class-string<Event>>
     */
    public array $events { get => array_keys($this->eventHandlers); }

    /**
     * @var array<class-string<Call<*>>, Handler<*, Call<*>>>
     */
    private array $callHandlers = [];

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param non-empty-list<MessageClass<TMessage>> $messageClasses
     * @param callable(Envelope<TMessage>, Context): TResult $handler
     * @param ?non-empty-string $id
     * @param list<Middleware> $middleware
     */
    public function with(array $messageClasses, callable $handler, array $middleware = [], ?string $id = null): self
    {
        $copy = clone $this;

        $handler = new Handler(
            id: $id ?? formatFunction($handler),
            handler: $handler,
            middleware: $middleware,
        );

        foreach ($messageClasses as $messageClass) {
            if ($messageClass->isCommand) {
                /** @phpstan-ignore assign.propertyType */
                $copy->commandHandlers[$messageClass->name] = $handler;

                continue;
            }

            if ($messageClass->isEvent) {
                /** @phpstan-ignore assign.propertyType */
                $copy->eventHandlers[$messageClass->name][] = $handler;

                continue;
            }

            /** @phpstan-ignore assign.propertyType */
            $copy->callHandlers[$messageClass->name] = $handler;
        }

        return $copy;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param callable(TMessage, Context, Stamps): (TResult|Result<TResult>) $handler
     * @param list<Middleware> $middleware
     * @param non-empty-list<MessageClass<TMessage>> $messageClasses
     * @param ?non-empty-string $id
     */
    public function withSimpleCallable(callable $handler, array $middleware = [], ?array $messageClasses = null, ?string $id = null): self
    {
        $reflection = new \ReflectionFunction($handler(...));

        if ($messageClasses === null) {
            $parameter = $reflection->getParameters()[0] ?? throw new \LogicException();
            $messageClasses = HandlerReflector::reflectMessagesClasses($parameter->getType());

            if ($messageClasses === []) {
                throw new \LogicException();
            }
        }

        return $this->with(
            /** @phpstan-ignore argument.type */
            messageClasses: $messageClasses,
            handler: static function (Envelope $envelope, Context $context) use ($handler): mixed {
                /** @phpstan-ignore argument.type */
                $result = $handler($envelope->message, $context, $envelope->stamps);

                if ($result instanceof Result) {
                    return $context->processResult($result);
                }

                return $result;
            },
            middleware: $middleware,
            id: $id ?? HandlerReflector::reflectId($reflection) ?? formatReflectedFunction($reflection),
        );
    }

    /**
     * @param Envelope<Command> $command
     */
    public function handleCommand(Envelope $command, Context $context): void
    {
        ($this->commandHandlers[$command->messageClass] ?? throw new \LogicException('No handler'))->handle($command, $context);
    }

    /**
     * @param Envelope<Event> $event
     */
    public function handleEvent(Envelope $event, Context $context): void
    {
        foreach ($this->eventHandlers[$event->messageClass] ?? [] as $handler) {
            $handler->handle($event, $context);
        }
    }

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function handleCall(Envelope $call, Context $context): mixed
    {
        return ($this->callHandlers[$call->messageClass] ?? throw new \LogicException('No handler'))->handle($call, $context);
    }
}
