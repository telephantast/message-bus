<?php

declare(strict_types=1);

use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\Persistence\InMemoryStorage;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class Service
{
    /**
     * @param HandlerContext<Now|Pong> $context
     */
    public static function handlePing(Ping $ping, HandlerContext $context): void
    {
        dump('Entered ' . __METHOD__);

        $now = $context->dispatch(new Now());

        $context->dispatch(
            new Pong(sprintf('Received "%s" at %s.', $ping->text, $now->format('c'))),
        );

        dump('Leaving ' . __METHOD__);
    }

    public static function handleNow(Now $now): DateTimeImmutable
    {
        dump('Entered ' . __METHOD__);

        $date = new DateTimeImmutable();

        dump('Leaving ' . __METHOD__);

        return $date;
    }

    public static function onPong(Pong $pong): void
    {
        dump('Entered ' . __METHOD__);

        dump($pong);

        dump('Leaving ' . __METHOD__);
    }
}

$messageBus = new MessageBus(
    storage: new InMemoryStorage(),
    syncHandlers: new Handlers()
        ->with(Service::handlePing(...))
        ->with(Service::handleNow(...))
        ->with(Service::onPong(...)),
);
$messageBus->dispatch(new Ping('Hi!'));
