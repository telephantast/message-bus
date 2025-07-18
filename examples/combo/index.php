<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Examples\Combo;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Postgres\PostgresTransaction;
use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\EndpointConfig;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageMatcher\Namespaced;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
use Thesis\MessageBus\Stamps;
use Thesis\MessageBus\Transport\InMemoryTransport;
use function Thesis\MessageBus\Handler\events;

require_once __DIR__ . '/../../vendor/autoload.php';

final readonly class Ping implements Command
{
    public function __construct(
        public string $text,
    ) {}
}

final readonly class Pong implements Event
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}

final readonly class App
{
    /**
     * @param Context<PostgresTransaction> $context
     * @return Result<null>
     */
    public static function ping(Ping $ping, Context $context): Result
    {
        $now = $context->invoke(new GetTimestamp());
        $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return events(new Pong($text));
    }

    public static function getTimestamp(GetTimestamp $_): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * @param Context<PostgresTransaction> $context
     */
    public static function onPong(Pong $pong, Context $context, Stamps $stamps): void
    {
        dump($pong, $stamps);
    }
}

$storage = new PostgresStorage(
    new PostgresConnectionPool(
        PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
    ),
);

$transport = new InMemoryTransport();

$messageBus = MessageBus::build([
    'test' => new EndpointConfig(
        handlers: Handlers::of(PostgresTransaction::class)
            ->withBasic(App::ping(...))
            ->withBasic(App::onPong(...))
            ->withBasic(App::getTimestamp(...)),
        handlesCommand: new Namespaced(__NAMESPACE__),
        publishesEvent: new Namespaced(__NAMESPACE__),
        handlesCall: new Namespaced(__NAMESPACE__),
        storage: $storage,
        transport: $transport,
    ),
]);

$messageBus->setup();
$messageBus->run();

$messageBus->send(new Ping('Hello!'));

while (!$transport->delivered) {
    $transport->deliver();
}
