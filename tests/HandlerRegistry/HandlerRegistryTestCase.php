<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use PHPUnit\Framework\TestCase;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\NullHandler;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\TestCommand;
use Thesis\MessageBus\TestEvent;

abstract class HandlerRegistryTestCase extends TestCase
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param array<class-string<TMessage>, Handler<TResult, TMessage>> $messageClassToHandler
     */
    abstract protected function createHandlerRegistry(array $messageClassToHandler): HandlerRegistry;

    final public function testGet(): void
    {
        $handler = new NullHandler();

        $handlerRegistry = $this->createHandlerRegistry([
            TestCommand::class => $handler,
        ]);

        self::assertSame($handler, $handlerRegistry->get(TestCommand::class));
    }

    final public function testGetHandlerNotFound(): void
    {
        self::expectException(HandlerNotFound::class);

        $handlerRegistry = $this->createHandlerRegistry([]);

        $handlerRegistry->get(TestCommand::class);
    }

    final public function testGetHandlerForEventNotFound(): void
    {
        $handlerRegistry = $this->createHandlerRegistry([]);

        $handler = $handlerRegistry->get(TestEvent::class);

        self::assertInstanceOf(CallableHandler::class, $handler);
    }

    final public function testFind(): void
    {
        $handler = new NullHandler();

        $handlerRegistry = $this->createHandlerRegistry([
            TestCommand::class => $handler,
        ]);

        self::assertSame($handler, $handlerRegistry->find(TestCommand::class));
    }

    final public function testFindHandlerNotFound(): void
    {
        $handlerRegistry = $this->createHandlerRegistry([]);

        self::assertNull($handlerRegistry->find(TestCommand::class));
    }
}
