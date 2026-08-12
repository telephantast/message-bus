<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Postgres\PostgresLink;
use Revolt\EventLoop;
use Thesis\Headers;
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
use Thesis\MessageBus\Protocol\RoutedCorrelationId;
use function Amp\async;
use function Amp\Future\awaitFirst;
use function Amp\trapSignal;
use const Thesis\MessageBus\Protocol\CORRELATION_ID;

require_once __DIR__ . '/../vendor/autoload.php';

#[Command]
final readonly class Register
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

#[Event]
final readonly class Registered
{
    public function __construct(
        public int $id,
    ) {}
}

#[Command]
final readonly class GetName
{
    public function __construct(
        public int $id,
    ) {}
}

#[Reply]
final readonly class Name
{
    public function __construct(
        public string $name,
    ) {}
}

final class App
{
    /**
     * @var array<int, string>
     */
    private array $names = [];

    public function registerHandler(Register $command, HandlerContext $context): void
    {
        dump([__METHOD__, $command]);

        $this->names[$command->id] = $command->name;

        $context->publish(new Registered($command->id));
    }

    public function onRegistered(Registered $event, HandlerContext $context): void
    {
        dump([__METHOD__, $event]);

        $context->send(
            command: new GetName($event->id),
            headers: new Headers()->with(CORRELATION_ID, new RoutedCorrelationId('name1', 'x')),
        );
    }

    public function getName(GetName $command, HandlerContext $context): void
    {
        dump([__METHOD__, $command]);

        $name = $this->names[$command->id] ?? throw new RuntimeException('No name for ' . $command->id);

        $context->reply(new Name($name));
    }

    public function name1(Name $name): void
    {
        dump([__METHOD__, $name]);
    }

    public function name2(Name $name): void
    {
        dump([__METHOD__, $name]);
    }
}

$postgres = new PostgresConnectionPool(
    new PostgresConfig(
        host: '0.0.0.0',
        user: 'thesis',
        database: 'thesis',
    ),
);

$app = new App();

$endpoint = Endpoint::transactional(
    name: 'registration',
    handlerRegistry: Handlers::tx(PostgresLink::class)
        ->with(Register::class, $app->registerHandler(...))
        ->with(Registered::class, $app->onRegistered(...))
        ->with(GetName::class, $app->getName(...))
        ->with(Name::class, $app->name1(...), qualifier: 'name1')
        ->with(Name::class, $app->name2(...), qualifier: 'name2'),
    transport: new PgmqTransport($postgres),
    transactionScopeFactory: new PostgresTransactionScopeFactory($postgres),
    deduplicator: new PostgresDeduplicator($postgres),
    serializer: new PhpNativeSerializer(),
);

$endpoint->setup();

$sendId = EventLoop::repeat(2, static function () use ($endpoint): void {
    /** @var int */
    static $id = 0;

    $endpoint->send(new Register(++$id, uniqid()));
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
