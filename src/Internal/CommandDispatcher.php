<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\CommandSender;

final readonly class CommandDispatcher
{
    /**
     * @param array<non-empty-string, CommandSender> $senders
     */
    public function __construct(
        private Router $router,
        private array $senders,
    ) {}

    /**
     * @param class-string $commandClass
     * @return non-empty-string
     */
    public function route(string $commandClass): string
    {
        return $this->router->route($commandClass);
    }

    /**
     * @param non-empty-list<Envelope> $commands
     */
    public function send(array $commands): void
    {
        $routedCommandsByEndpoint = [];

        foreach ($commands as $command) {
            $routedCommandsByEndpoint[$this->router->route($command->messageClass)][] = $command;
        }

        foreach ($routedCommandsByEndpoint as $endpoint => $routedCommands) {
            $this->senders[$endpoint]->send($endpoint, $routedCommands);
        }
    }
}
