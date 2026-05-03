<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\CommandRouter;
use Thesis\MessageBus\Endpoint;
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
final readonly class Router
{
    /**
     * @param list<Endpoint<*>> $endpoints
     */
    public static function build(array $endpoints, ?CommandRouter $userRouter = null): self
    {
        $endpointRoutes = [];

        foreach ($endpoints as $endpoint) {
            foreach ($endpoint->handlers->messageClasses as $messageClass) {
                $endpointRoutes[$messageClass] = $endpoint->name;
            }
        }

        return new self(
            commandRouter: new CommandRouter\Internal\Memoized(
                new CommandRouter\Chain([
                    ...($userRouter === null ? [] : [$userRouter]),
                    new CommandRouter\Internal\Attribute(),
                    new CommandRouter\Map($endpointRoutes),
                ]),
            ),
        );
    }

    private function __construct(
        private CommandRouter $commandRouter,
    ) {}

    public function route(Command|Event|Reply $message, ?Metadata $causeMetadata = null): Direct|Fanout
    {
        return match ($message::class) {
            Command::class => new Direct(
                $message->destination
                    ?? $this->commandRouter->routeCommand($message->payload::class)
                    ?? throw new CannotRoute('Cannot route command'),
            ),
            Event::class => new Fanout(
                $message->payload::class,
            ),
            Reply::class => new Direct(
                $causeMetadata->origin ?? throw new CannotReply('Cannot route reply'),
            ),
        };
    }
}
