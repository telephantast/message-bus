<?php

declare(strict_types=1);

use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\Context;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Transport\InMemoryTransport;
use Thesis\MessageBus\Transport\Router;
use function Thesis\MessageBus\events;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class App
{
    /**
     * @return Result<null>
     */
    public static function ping(Ping $ping, Context $context): Result
    {
        $now = $context->get(Invoker::class(GetTimestamp::class))->invoke(new GetTimestamp());
        $text = sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return events(new Pong($text));
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

$transport = new InMemoryTransport();
$messageBus = new MessageBus(
    endpoints: [
        'test' => new Handlers()
            ->with([Ping::class], new CallableHandler(App::ping(...)))
            ->with([Pong::class], new CallableHandler(App::onPong(...)))
            ->with([GetTimestamp::class], new CallableHandler(App::getTimestamp(...))),
    ],
    sender: $transport,
    publisher: $transport,
    consumer: $transport,
    router: new Router\Map([
        Ping::class => 'test',
        GetTimestamp::class => 'test',
    ]),
);
$transport->subscribe('test', [Pong::class]);
$messageBus->run('test');

$messageBus->send(new Ping('Hello!'));
