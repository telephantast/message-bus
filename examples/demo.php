<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Postgres\PostgresLink;
use Revolt\EventLoop;
use Thesis\MessageBus\AmpPostgres\PostgresDeduplicator;
use Thesis\MessageBus\AmpPostgres\PostgresTransactionScopeFactory;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Handling\Handlers;
use Thesis\MessageBus\Metadata\Command;
use Thesis\MessageBus\Metadata\Event;
use Thesis\MessageBus\Metadata\Reply;
use Thesis\MessageBus\Pgmq\PgmqTransport;
use Thesis\MessageBus\Protocol\PhpNativeSerializer;
use function Amp\async;
use function Amp\Future\awaitFirst;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';

#[Command]
final readonly class Register
{
    public function __construct(
        public string $name,
    ) {}
}

#[Event]
final readonly class Registered
{
    public function __construct(
        public string $name,
    ) {}
}

#[Reply]
final readonly class RegistrationAccepted
{
    public function __construct(
        public string $name,
    ) {}
}

final readonly class App
{
    public static function register(Register $command, HandlerContext $context): void
    {
        dump($command::class);

        $context->publish(new Registered($command->name));
    }

    public static function registered(Registered $event, HandlerContext $context): void
    {
        dump($event::class);

        $context->reply(new RegistrationAccepted($event->name));
    }

    public static function accepted(RegistrationAccepted $reply): void
    {
        dump($reply::class);
    }
}

$postgres = new PostgresConnectionPool(
    new PostgresConfig(
        host: '0.0.0.0',
        user: 'thesis',
        database: 'thesis',
    ),
);

$endpoint = Endpoint::transactional(
    name: 'registration',
    handlerRegistry: Handlers::tx(PostgresLink::class)
        ->with(Register::class, App::register(...))
        ->with(Registered::class, App::registered(...))
        ->with(RegistrationAccepted::class, App::accepted(...)),
    transport: new PgmqTransport($postgres),
    transactionScopeFactory: new PostgresTransactionScopeFactory($postgres),
    deduplicator: new PostgresDeduplicator($postgres),
    serializer: new PhpNativeSerializer(),
);

$endpoint->setup();

$sendId = EventLoop::repeat(2, static function () use ($endpoint): void {
    $endpoint->send(new Register('Valentin'));
});

$consumer = $endpoint->startConsumer();

awaitFirst([
    async(static function () use ($consumer, $sendId): void {
        trapSignal([SIGINT, SIGTERM]);

        EventLoop::cancel($sendId);

        $consumer->stop();
        $consumer->awaitCompletion();
    }),
    async($consumer->awaitCompletion(...)),
]);
