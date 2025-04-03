<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\TestEvent;

#[CoversClass(EventHandlers::class)]
final class EventHandlersTest extends TestCase
{
    /**
     * @param list<non-empty-string> $ids
     * @param non-empty-string $expectedId
     */
    #[TestWith([[], '[]'])]
    #[TestWith([['a'], '["a"]'])]
    #[TestWith([['b', 'a', 'a'], '["a","a","b"]'])]
    public function testId(array $ids, string $expectedId): void
    {
        $eventHandlers = new EventHandlers(array_map(
            static function (string $id): NullHandler {
                /** @var NullHandler<Event> */
                return new NullHandler($id);
            },
            $ids,
        ));

        $id = $eventHandlers->id();

        self::assertSame($expectedId, $id);
    }

    public function testItCallsEveryHandler(): void
    {
        $context = new Context(
            messageBus: new MessageBus(),
            envelope: new Envelope(new TestEvent(), 'event'),
        );
        $handler1 = $this->createMock(Handler::class);
        $handler1->expects(self::once())
            ->method('handle')
            ->with($context);
        $handler2 = $this->createMock(Handler::class);
        $handler2->expects(self::once())
            ->method('handle')
            ->with($context);
        $eventHandlers = new EventHandlers([$handler1, $handler2]);

        $eventHandlers->handle($context);
    }
}
