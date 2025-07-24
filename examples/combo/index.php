<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Examples\Combo;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\MessageBusBuilder;
use Thesis\MessageBus\MessageMatcher\Namespaced;
use Thesis\MessageBus\Method;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Stamps;
use Thesis\MessageBus\Transport\InMemory\InMemoryTransport;
use function Amp\trapSignal;
use function Thesis\MessageBus\Handler\publish;

require_once __DIR__ . '/../../vendor/autoload.php';

final readonly class Ping
{
    public function __construct(
        public string $text,
    ) {}
}

final readonly class Pong
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @implements Method<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Method {}

final readonly class App
{
    /**
     * @param Context<object> $context
     * @return Result<null>
     */
    public static function ping(Ping $ping, Context $context): Result
    {
        $now = $context->invoke(new GetTimestamp());
        $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return publish(new Pong($text));
    }

    public static function getTimestamp(GetTimestamp $_): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * @param Context<object> $context
     */
    public static function onPong(Pong $pong, Context $context, Stamps $stamps): void
    {
        dump($pong, $stamps);
    }
}

/*$storage = new PostgresStorage(
    new PostgresConnectionPool(
        PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
    ),
);*/
$storage = new InMemoryStorage();

$transport = new InMemoryTransport();

$messageBus = new MessageBusBuilder()
    ->consumer(
        name: 'app',
        handlers: new CommandHandlers()->withFeatured(App::ping(...)),
        storage: $storage,
        transport: $transport,
    )
    ->publisher(
        events: new Namespaced(__NAMESPACE__),
        transport: $transport,
    )
    ->subscription(
        name: 'app',
        listeners: new EventListeners()->withFeatured(App::onPong(...)),
        storage: $storage,
    )
    ->service(
        name: 'app',
        handlers: new MethodHandlers()->withFeatured(App::getTimestamp(...)),
        storage: $storage,
    )
    ->build();

$messageBus->setup();

$messageBus->send(new Ping('Hello!'));

$run = $messageBus->run();

trapSignal(SIGINT);

$run->stop();
