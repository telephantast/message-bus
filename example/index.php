<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Example;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\EndpointConfig;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Envelope\MessageId;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageMatcher\Namespaced;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
use Thesis\MessageBus\Stamps;
use Thesis\MessageBus\Transport\InMemoryTransport;
use function Amp\delay;
use function Thesis\MessageBus\Handler\events;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @implements Call<null>
 */
final readonly class Ping implements Command, Call
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
     * @return Result<null>
     */
    public static function ping(Ping $ping, Context $context): Result
    {
        $now = $context->invoke(new GetTimestamp());
        $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return events(new Pong($text));
    }

    public static function getTimestamp(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public static function onPong(Pong $pong, Context $context, Stamps $stamps): void
    {
        dump($pong, $stamps);
    }
}

$transport = new InMemoryTransport();
$messageBus = MessageBus::build(
    endpointConfigs: [
        'test' => new EndpointConfig(
            handler: new Handlers()
                ->with(new CallableHandler([Ping::class], App::ping(...)))
                ->with(new CallableHandler([Pong::class], App::onPong(...)))
                ->with(new CallableHandler([GetTimestamp::class], App::getTimestamp(...))),
            handlesCommand: new Namespaced(__NAMESPACE__),
            publishesEvent: new Namespaced(__NAMESPACE__),
            handlesCall: new Namespaced(__NAMESPACE__),
            storage: new PostgresStorage(
                new PostgresConnectionPool(
                    PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
                ),
            ),
            transport: $transport,
        ),
    ],
);

$messageBus->setup();
$messageBus->run();

$messageBus->send(
    new Envelope(new Ping('Hello!'), [
        new MessageId('ping_as_command.' . bin2hex(random_bytes(10))),
    ]),
);
$messageBus->send(
    new Envelope(new Ping('Hello!'), [
        new MessageId('ping_as_call.' . bin2hex(random_bytes(10))),
    ]),
);

while (!$transport->delivered) {
    delay(0);
}
