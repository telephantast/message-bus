<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\TestCommand;

#[CoversClass(InMemoryTransport::class)]
final class InMemoryTransportTest extends TestCase
{
    public function testItAddsPublishedEnvelopesToInFlight(): void
    {
        $transport = new InMemoryTransport();
        $envelope = new Envelope(new TestCommand(), 'a');

        $transport->publish([$envelope]);

        self::assertSame([$envelope], $transport->inFlightEnvelopes());
    }

    public function testItDelivers(): void
    {
        $transport = new InMemoryTransport();
        $transport->setup([TestCommand::class => ['q']]);
        $delivered = [];
        $transport->consume(new Consumer(
            queue: 'q',
            handlerRegistry: HandlerRegistry::oneCallableHandler(
                static function (TestCommand $c) use (&$delivered): void {
                    $delivered[] = $c->data;
                },
            ),
        ));

        $transport->publish([
            new Envelope(new TestCommand('a'), 'a'),
        ]);
        $transport->publish([
            new Envelope(new TestCommand('b'), 'b'),
            new Envelope(new TestCommand('c'), 'c'),
        ]);
        $transport->deliver();

        self::assertSame([], $transport->inFlightEnvelopes());
        self::assertSame(['a', 'b', 'c'], $delivered);
    }

    public function testItDeliversToDifferentQueues(): void
    {
        $transport = new InMemoryTransport();
        $transport->setup([TestCommand::class => ['q1', 'q2']]);
        $deliveredQ1 = [];
        $transport->consume(new Consumer(
            queue: 'q1',
            handlerRegistry: HandlerRegistry::oneCallableHandler(
                static function (TestCommand $c) use (&$deliveredQ1): void {
                    $deliveredQ1[] = $c->data;
                },
            ),
        ));
        $deliveredQ2 = [];
        $transport->consume(new Consumer(
            queue: 'q2',
            handlerRegistry: HandlerRegistry::oneCallableHandler(
                static function (TestCommand $c) use (&$deliveredQ2): void {
                    $deliveredQ2[] = $c->data;
                },
            ),
        ));

        $transport->publish([
            new Envelope(new TestCommand('a'), 'a'),
        ]);
        $transport->deliver();

        self::assertSame([], $transport->inFlightEnvelopes());
        self::assertSame(['a'], $deliveredQ1);
        self::assertSame(['a'], $deliveredQ2);
    }

    public function testItDoesNotDeliverIfNotSetup(): void
    {
        $transport = new InMemoryTransport();
        $transport->consume(new Consumer(
            queue: 'q',
            handlerRegistry: HandlerRegistry::oneCallableHandler(
                static fn(TestCommand $_): never => self::fail(),
            ),
        ));

        $transport->publish([new Envelope(new TestCommand(), 'a')]);
        $transport->deliver();

        self::assertSame([], $transport->inFlightEnvelopes());
    }

    public function testItDoesNotDeliverIfSubscriptionCancelled(): void
    {
        $transport = new InMemoryTransport();
        $cancel = $transport->consume(new Consumer(
            queue: 'q',
            handlerRegistry: HandlerRegistry::oneCallableHandler(
                static fn(TestCommand $_): never => self::fail(),
            ),
        ));
        $cancel();

        $transport->publish([new Envelope(new TestCommand(), 'a')]);
        $transport->deliver();

        self::assertSame([], $transport->inFlightEnvelopes());
    }
}
