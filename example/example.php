<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Example;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\Context;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageClassMatcher\Namespaced;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Transport\InMemoryTransport;
use function Thesis\MessageBus\events;
use function Thesis\MessageBus\invokerClass;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/messages.php';

final readonly class App
{
    /**
     * @return Result<null>
     */
    public static function ping(Ping $ping, Context $context): Result
    {
        $now = $context->get(invokerClass(GetTimestamp::class))->invoke(new GetTimestamp());
        $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return events(new Pong($text));
    }

    public static function getTimestamp(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public static function onPong(Pong $pong): void
    {
        dump($pong);
    }
}

$transport = new InMemoryTransport();
$messageBus = new MessageBus(
    endpoints: [
        new Endpoint(
            name: 'test',
            handler: new Handlers()
                ->with(new CallableHandler([Ping::class], App::ping(...)))
                ->with(new CallableHandler([Pong::class], App::onPong(...)))
                ->with(new CallableHandler([GetTimestamp::class], App::getTimestamp(...))),
            handlesCommand: new Namespaced(__NAMESPACE__),
            publishesEvent: new Namespaced(__NAMESPACE__),
            handlesCall: new Namespaced(__NAMESPACE__),
            transport: $transport,
        ),
    ],
);

$messageBus->setup();
$messageBus->run('test');

$messageBus->send(new Ping('Hello!'));
