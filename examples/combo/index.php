<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Examples\Combo;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\MessageBusBuilder;
use Thesis\MessageBus\MessageMatcher\Namespaced;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Transport\InMemory\InMemoryTransport;
use function Amp\trapSignal;
use function Thesis\MessageBus\Handler\events;

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
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}

/*$storage = new PostgresStorage(
    new PostgresConnectionPool(
        PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
    ),
);*/
$storage = new InMemoryStorage();

$transport = new InMemoryTransport();

$messageBus = new MessageBusBuilder()
    ->queue(
        name: 'commands',
        handlers: new CommandHandlers()
            ->withFeatured(
                static function (Ping $ping): Result {
                    $now = new \DateTimeImmutable(); // $context->invoke(new GetTimestamp());
                    $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

                    return events(new Pong($text));
                },
            ),
        storage: $storage,
        receiver: $transport,
    )
    ->publisher(
        events: new Namespaced(__NAMESPACE__),
        publisher: $transport,
    )
    ->subscription(
        name: 'subscription',
        listeners: new EventListeners()
            ->withFeatured(
                static function (Pong $pong): void {
                    dump($pong);
                },
            ),
        storage: $storage,
    )
    ->build();

$messageBus->setup();

$messageBus->send(new Ping('Hello!'));

$run = $messageBus->start();

trapSignal(SIGINT);

$run->stop();
