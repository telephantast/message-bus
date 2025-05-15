<?php

declare(strict_types=1);

use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\MessageBus;

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
        dump(1);
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
    = new MessageBus()
    ->syncHandler(Handlers::handlePing(...))
    ->syncHandler(Handlers::handleNow(...))
    ->syncHandler(Handlers::onPong(...))
    ->dispatch(new Ping('Hi!'));
