<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Example;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Call;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Handler\CallableHandler;
use Thesis\MessageBus\Handler\CallableHandler\FromContext;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\MessageBus;
use Thesis\MessageBus\MessageClassMatcher\Namespaced;
use Thesis\MessageBus\Transport\InMemoryTransport;
use function Thesis\MessageBus\Handler\events;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class Ping implements Command
{
    public function __construct(
        public string $text,
    ) {}
}

final readonly class Pong implements Event
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}


final readonly class App
{
    /**
     * @param Invoker<GetTimestamp> $invoker
     * @return Result<null>
     */
    public static function ping(Ping $ping, #[FromContext] Invoker $invoker): Result
    {
        $now = $invoker->invoke(new GetTimestamp());
        $text = \sprintf('Received "%s" at %s.', $ping->text, $now->format('c'));

        return events(new Pong($text));
    }

    public static function getTimestamp(GetTimestamp $_query): \DateTimeImmutable
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
                ->with(new CallableHandler(App::ping(...)))
                ->with(new CallableHandler(App::onPong(...)))
                ->with(new CallableHandler(App::getTimestamp(...))),
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
