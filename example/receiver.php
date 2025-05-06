<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Thesis\Amqp\Config;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\ArrayHandlerRegistry;
use Thesis\MessageBus\Handling\CallableHandler;
use Thesis\MessageBus\Handling\HandlingContext;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
use Thesis\MessageBus\Persistence\Postgres\PostgresTransaction;
use Thesis\MessageBus\Transport\Amqp\AmqpTransport;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

$endpoint = new Endpoint(
    name: 'receiver',
    storage: new PostgresStorage(
        connection: new PostgresConnectionPool(
            PostgresConfig::fromString('host=localhost user=app password=!ChangeMe! db=app'),
        ),
    ),
    transport: new AmqpTransport(Config::default()),
    asyncHandlerRegistry: new ArrayHandlerRegistry([
        Ping::class => new CallableHandler(
            /** @param HandlingContext<PostgresTransaction> $context */
            static function (Ping $command, HandlingContext $context, Envelope $envelope): void {
                dump($envelope);
                $context->dispatch(new Pong($command->text));
            },
        ),
    ]),
);
$endpoint->run();

trapSignal([SIGINT, SIGTERM]);

$endpoint->stop();
