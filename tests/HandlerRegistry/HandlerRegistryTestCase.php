<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use PHPUnit\Framework\TestCase;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\TestEvent;
use Thesis\MessageBus\TestMessage;
use Thesis\MessageBus\TestMessageHandler;

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
        $handler = new TestMessageHandler();

        $handlerRegistry = $this->createHandlerRegistry([
            TestMessage::class => $handler,
        ]);

        self::assertSame($handler, $handlerRegistry->get(TestMessage::class));
    }

    final public function testGetHandlerNotFound(): void
    {
        self::expectException(HandlerNotFound::class);

        $handlerRegistry = $this->createHandlerRegistry([]);

        $handlerRegistry->get(TestMessage::class);
    }

    final public function testGetHandlerForEventNotFound(): void
    {
        $handlerRegistry = $this->createHandlerRegistry([]);

        $handler = $handlerRegistry->get(TestEvent::class);

        self::assertInstanceOf(Handler\CallableHandler::class, $handler);
    }

    final public function testFind(): void
    {
        $handler = new TestMessageHandler();

        $handlerRegistry = $this->createHandlerRegistry([
            TestMessage::class => $handler,
        ]);

        self::assertSame($handler, $handlerRegistry->find(TestMessage::class));
    }

    final public function testFindHandlerNotFound(): void
    {
        $handlerRegistry = $this->createHandlerRegistry([]);

        self::assertNull($handlerRegistry->find(TestMessage::class));
    }
}
