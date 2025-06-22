<?php

declare(strict_types=1);

use Thesis\MessageBus\Call\CallableCallHandler;
use Thesis\MessageBus\Call\CallHandlers;
use Thesis\MessageBus\Command\CallableCommandHandler;
use Thesis\MessageBus\Command\CommandHandlers;
use Thesis\MessageBus\Event\CallableEventListener;
use Thesis\MessageBus\Event\EventListeners;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\Result;
use function Thesis\MessageBus\publish;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class App
{
    /**
     * @param Invoker<GetTimestamp> $invoker
     * @return Result<null>
     */
    public static function ping(Ping $ping, Invoker $invoker): Result
    {
        $now = $invoker->invoke(new GetTimestamp());
        $text = sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return publish(new Pong($text));
    }

    public static function getTimestamp(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public static function onPong(Pong $pong): void
    {
        dump($pong);
    }
}

$messageBus = new MessageBus(
    commandHandler: new CommandHandlers()
        ->with([Ping::class], new CallableCommandHandler(App::ping(...))),
    eventListener: new EventListeners()
        ->with([Pong::class], new CallableEventListener(App::onPong(...))),
    callHandler: new CallHandlers()
        ->with([GetTimestamp::class], new CallableCallHandler(App::getTimestamp(...))),
);

$messageBus->send(new Ping('Hello!'));
