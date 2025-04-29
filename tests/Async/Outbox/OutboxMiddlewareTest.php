<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\MessageBus\Async\InConsumer;
use Thesis\MessageBus\Async\InMemoryTransport;
use Thesis\MessageBus\Async\PublishHandler;
use Thesis\MessageBus\Async\TransportPublish;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\ContextAttributes;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageId\IncrementalMessageIdGenerator;
use Thesis\MessageBus\TestCommand;
use Thesis\MessageBus\TestEvent;
use Thesis\MessageBus\Transaction\PdoTransactionProvider;

#[CoversClass(OutboxMiddleware::class)]
final class OutboxMiddlewareTest extends TestCase
{
    public function testPositiveControllerScenario(): void
    {
        $connection = new \PDO('sqlite::memory:');
        $transactionProvider = new PdoTransactionProvider($connection);
        $outboxStorage = new PdoOutboxStorage($connection);
        $outboxStorage->setup();
        $transport = new InMemoryTransport();
        $transport->setup([TestEvent::class => ['event']]);
        $outboxMiddleware = new OutboxMiddleware($outboxStorage, $transactionProvider, $transport);
        /** @psalm-suppress InvalidArgument */
        $messageBus = new MessageBus(
            handlerRegistry: HandlerRegistry::builder()
                ->addCallableHandler(
                    static function (TestCommand $command, Context $context): void {
                        $context->dispatch(new TestEvent($command->data));
                    },
                    [$outboxMiddleware],
                )
                ->addHandler(TestEvent::class, new PublishHandler($this->createMock(TransportPublish::class)))
                ->build(),
            messageIdGenerator: new IncrementalMessageIdGenerator(),
        );

        $messageBus->dispatch(new TestCommand('data'));

        self::assertSame(
            [],
            $outboxStorage->get('1', OutboxMiddleware::CONTROLLER_QUEUE)?->envelopes,
        );
        self::assertEquals(
            [new TestEvent('data')],
            array_column($transport->inFlightEnvelopes(), 'message'),
        );
    }

    public function testPositiveConsumerScenario(): void
    {
        $connection = new \PDO('sqlite::memory:');
        $transactionProvider = new PdoTransactionProvider($connection);
        $outboxStorage = new PdoOutboxStorage($connection);
        $outboxStorage->setup();
        $transport = new InMemoryTransport();
        $transport->setup([TestEvent::class => ['event']]);
        $outboxMiddleware = new OutboxMiddleware($outboxStorage, $transactionProvider, $transport);
        /** @psalm-suppress InvalidArgument */
        $messageBus = new MessageBus(
            handlerRegistry: HandlerRegistry::builder()
                ->addCallableHandler(
                    static function (TestCommand $command, Context $context): void {
                        $context->dispatch(new TestEvent($command->data));
                    },
                    [$outboxMiddleware],
                )
                ->addHandler(TestEvent::class, new PublishHandler($this->createMock(TransportPublish::class)))
                ->build(),
            messageIdGenerator: new IncrementalMessageIdGenerator(),
        );

        $messageBus->dispatch(
            message: new TestCommand('data'),
            attributes: new ContextAttributes([new InConsumer('queue')]),
        );

        self::assertSame([], $outboxStorage->get('1', 'queue')?->envelopes);
        self::assertEquals(
            [new TestEvent('data')],
            array_column($transport->inFlightEnvelopes(), 'message'),
        );
    }
}
