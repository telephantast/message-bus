<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Revolt\EventLoop;
use Thesis\Amqp\Config;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\ArrayHandlerRegistry;
use Thesis\MessageBus\Handling\CallableHandler;
use Thesis\MessageBus\Handling\HandlingContext;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;
use Thesis\MessageBus\Transport\Amqp\AmqpTransport;
use function Amp\async;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

$endpoint = new Endpoint(
    name: 'sender',
    storage: new PostgresStorage(
        connection: new PostgresConnectionPool(
            PostgresConfig::fromString('host=localhost user=app password=!ChangeMe! db=app'),
        ),
    ),
    transport: new AmqpTransport(Config::default()),
    asyncHandlerRegistry: new ArrayHandlerRegistry([
        Pong::class => new CallableHandler(
            static function (Pong $event, HandlingContext $context, Envelope $envelope): void {
                dump($envelope);
            },
        ),
    ]),
);
$endpoint->run();

echo 'Type a message and hit <Enter>: ';

$callbackId = EventLoop::onReadable(STDIN, static function () use ($endpoint): void {
    $text = fgets(STDIN);
    assert($text !== false);
    $text = trim($text);

    async(static fn(): null => $endpoint->dispatch(new Ping($text)));

    echo sprintf("Message `%s` sent.\nType a message and hit <Enter>: ", $text);
});

trapSignal([SIGINT, SIGTERM]);

EventLoop::cancel($callbackId);
$endpoint->stop();
