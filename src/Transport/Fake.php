<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

enum Fake implements CommandSender, CommandReceiver, EventPublisher, EventReceiver, CallClient, CallServer
{
    case Instance;

    public function send(string $toEndpoint, array $commands): void
    {
        throw new \LogicException('Not supported');
    }

    public function consumeCommands(string $endpoint, callable $consumer): void {}

    public function publish(string $atEndpoint, array $events): void
    {
        throw new \LogicException('Not supported');
    }

    public function subscribe(string $endpoint, array $toEvents): void
    {
        throw new \LogicException('Not supported');
    }

    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope<Command|Event>): void $consumer
     */
    public function consumeEvents(string $endpoint, callable $consumer): void {}

    public function invoke(string $endpoint, Envelope $call): mixed
    {
        throw new \LogicException('Not supported');
    }

    public function serve(string $endpoint, callable $handler): void {}
}
