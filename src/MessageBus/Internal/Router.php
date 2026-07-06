<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\CommandDraft;
use Thesis\MessageBus\CommandRouter;
use Thesis\MessageBus\EventDraft;
use Thesis\MessageBus\Exception\CannotReply;
use Thesis\MessageBus\Exception\CannotRoute;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\ReplyDraft;
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

    public function route(CommandDraft|EventDraft|ReplyDraft $message, ?Metadata $causeMetadata = null): Direct|Fanout
    {
        return match ($message::class) {
            CommandDraft::class => $this->routeCommand($message),
            EventDraft::class => new Fanout($message->payload::class),
            ReplyDraft::class => new Direct($causeMetadata->origin ?? throw new CannotReply('Cannot route reply')),
        };
    }

    private function routeCommand(CommandDraft $draft): Direct
    {
        if ($draft->destination !== null) {
            return new Direct($draft->destination);
        }

        $payloadClass = $draft->payload::class;

        if (isset($this->commandRoutes[$payloadClass])) {
            return $this->commandRoutes[$payloadClass];
        }

        $destination = $this->commandRouter->routeCommand($payloadClass)
            ?? throw new CannotRoute(\sprintf('Cannot route command "%s"', $payloadClass));

        return $this->commandRoutes[$payloadClass] = new Direct($destination);
    }
}
