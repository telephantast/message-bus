<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Thesis\Amqp\Client;
use Thesis\Amqp\Config;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\ArrayHandlerRegistry;
use Thesis\MessageBus\Handling\HandleContext;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
use Thesis\MessageBus\Transport\Amqp\AmqpTransport;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

$endpoint = new Endpoint(
    name: 'receiver',
    storage: new PostgresStorage(
        connection: new PostgresConnectionPool(
            PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
        ),
    ),
    transport: new AmqpTransport(new Client(Config::default())),
    asyncHandlerRegistry: ArrayHandlerRegistry::create()
        ->withCallableHandler(
            static function (Ping $command, HandleContext $context, Envelope $envelope): void {
                dump($envelope);
                $context->dispatch(new Pong($command->text));
            },
        ),
);
$endpoint->run();

trapSignal([SIGINT, SIGTERM]);

$endpoint->stop();
