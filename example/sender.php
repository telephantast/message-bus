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
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

$endpoint = new Endpoint(
    name: 'sender',
    storage: new PostgresStorage(
        connection: new PostgresConnectionPool(
            PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
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

    try {
        $endpoint->dispatch(new Ping($text));
        echo sprintf('Message `%s` successfully sent.', $text), PHP_EOL;
    } catch (Throwable $exception) {
        dump($exception);
    }

    echo 'Type a message and hit <Enter>: ';
});

trapSignal([SIGINT, SIGTERM]);

EventLoop::cancel($callbackId);
$endpoint->stop();
