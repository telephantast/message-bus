<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

enum Fake implements Sender, Subscriber, Publisher, Consumer, Client
{
    case Instance;

    public function invoke(string $endpoint, Envelope $call): mixed
    {
        throw new \LogicException('Invoking remote calls is not supported');
    }

    public function consume(string $endpoint, callable $handler): void
    {
        throw new \LogicException('Consuming commands and events supported');
    }

    public function publish(array $events): void
    {
        throw new \LogicException('Publishing events is not supported');
    }

    public function send(array $routedCommands): void
    {
        throw new \LogicException('Sending commands is not supported');
    }

    public function subscribe(string $endpoint, array $events): void
    {
        throw new \LogicException('Subscribing to events is not supported');
    }
}
