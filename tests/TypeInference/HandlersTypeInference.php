<?php

declare(strict_types=1);

namespace Thesis\MessageBus\TypeInference;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\TestCommand;
use Thesis\MessageBus\TestQuery;

abstract class HandlersTypeInference
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @template TRequiredMessage of Message
     * @param class-string<TMessage> $message
     * @param ?class-string<TRequiredMessage> $requiredMessage
     * @return Handler<TResult, TMessage, TRequiredMessage>
     */
    abstract public function handler(string $message, ?string $requiredMessage = null): Handler;

    /**
     * @return Handlers<Event|TestQuery, TestCommand>
     */
    final public function testTypesAreAdded(): Handlers
    {
        return new Handlers()->with($this->handler(TestQuery::class, TestCommand::class));
    }

    /**
     * @param Handlers<TestQuery, TestCommand> $handlers
     * @param HandlerContext<TestCommand> $context
     */
    final public function testCanHandle(Handlers $handlers, HandlerContext $context): void
    {
        $handlers->handle(new TestQuery(), $context);
    }
}
