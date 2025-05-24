<?php

declare(strict_types=1);

use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\MessageBus;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class Handlers
{
    /**
     * @param Dispatcher<Now|Pong> $dispatcher
     */
    public static function handlePing(Ping $ping, Dispatcher $dispatcher): void
    {
        dump('Entered ' . __METHOD__);

        $now = $dispatcher->dispatch(new Now());

        $dispatcher->dispatch(
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

$messageBus
    = new MessageBus()
    ->withHandler(Handlers::handlePing(...))
    ->withHandler(Handlers::handleNow(...))
    ->withHandler(Handlers::onPong(...))
    ->dispatch(new Ping('Hi!'));
