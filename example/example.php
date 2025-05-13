<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\Persistence\Postgres\PostgresStorage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class Handlers
{
    /**
     * @param HandlerContext<Now|Pong> $context
     */
    public static function handlePing(Ping $ping, HandlerContext $context): void
    {
        $context->dispatch(
            new Pong(sprintf(
                'Received "%s" at %s.',
                $ping->text,
                $context->dispatch(new Now())->format('c'),
            )),
        );
    }

    public static function handleNow(Now $now): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public static function onPong(Pong $pong): void
    {
        dump($pong);
    }
}

$messageBus
    = new MessageBus(
        storage: new PostgresStorage(new PostgresConnectionPool(
            PostgresConfig::fromString('host=localhost user=postgres password=postgres db=postgres'),
        )),
    )
    ->syncHandler(Handlers::handlePing(...))
    ->syncHandler(Handlers::handleNow(...))
    ->syncHandler(Handlers::onPong(...))
    ->dispatch(new Ping('Hi!'));
