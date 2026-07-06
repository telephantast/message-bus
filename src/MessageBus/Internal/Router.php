<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\CommandRouter;
use Thesis\MessageBus\Event;
use Thesis\MessageBus\Exception\CannotReply;
use Thesis\MessageBus\Exception\CannotRoute;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\Reply;
use Thesis\MessageBus\Route\Direct;
use Thesis\MessageBus\Route\Fanout;

/**
 * @internal
 */
final class Router
{
    /**
     * @var array<class-string, Direct>
     */
    private array $commandRoutes = [];

    public function __construct(
        private readonly CommandRouter $commandRouter,
    ) {}

    public function route(Command|Event|Reply $message, ?Metadata $causeMetadata = null): Direct|Fanout
    {
        return match ($message::class) {
            Command::class => $this->routeCommand($message),
            Event::class => new Fanout($message->payload::class),
            Reply::class => new Direct($causeMetadata->origin ?? throw new CannotReply('Cannot route reply')),
        };
    }

    private function routeCommand(Command $command): Direct
    {
        if ($command->destination !== null) {
            return new Direct($command->destination);
        }

        $payloadClass = $command->payload::class;

        if (isset($this->commandRoutes[$payloadClass])) {
            return $this->commandRoutes[$payloadClass];
        }

        $destination = $this->commandRouter->routeCommand($payloadClass)
            ?? throw new CannotRoute(\sprintf('Cannot route command "%s"', $payloadClass));

        return $this->commandRoutes[$payloadClass] = new Direct($destination);
    }
}
