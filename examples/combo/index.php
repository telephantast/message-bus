<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Examples\Combo;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Thesis\MessageBus\Call;
use Thesis\MessageBus\CommandHandlers;
use Thesis\MessageBus\EventListeners;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\MessageBusBuilder;
use Thesis\MessageBus\MessageMatcher\Namespaced;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
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

$postgresStorage = new PostgresStorage(
    new PostgresConnectionPool(
        PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
    ),
);

$inMemoryTransport = new InMemoryTransport();

$messageBus = new MessageBusBuilder()
    ->localCommandEndpoint(
        name: 'commands',
        handlers: new CommandHandlers()
            ->withFeatured(
                static function (Ping $ping): Result {
                    $now = new \DateTimeImmutable(); // $context->invoke(new GetTimestamp());
                    $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

                    return events(new Pong($text));
                },
            ),
        storage: $postgresStorage,
        receiver: $inMemoryTransport,
    )
    ->eventPublisher(
        name: 'publisher',
        events: new Namespaced(__NAMESPACE__),
        publisher: $inMemoryTransport,
    )
    ->eventSubscription(
        name: 'subscription',
        listeners: new EventListeners()
            ->withFeatured(
                static function (Pong $pong): void {
                    dump($pong);
                },
            ),
        storage: $postgresStorage,
    )
    ->build();

$messageBus->setup();

$messageBus->send(new Ping('Hello!'));

$cancellers = [
    $messageBus->startCommandConsumer('commands'),
    $messageBus->startSubscription('subscription'),
];

trapSignal(SIGINT);

foreach ($cancellers as $canceller) {
    $canceller->cancel();
}
