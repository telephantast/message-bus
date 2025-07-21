<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\CommandSender;

final readonly class CommandDispatcher
{
    /**
     * @param Router<non-empty-string> $router
     * @param array<non-empty-string, CommandSender> $senders
     */
    public function __construct(
        private Router $router,
        private array $senders,
    ) {}

    /**
     * @param non-empty-list<Envelope> $commands
     */
    public function send(array $commands): void
    {
        $routedCommandsByQueue = [];

        foreach ($commands as $command) {
            $routedCommandsByQueue[$this->router->route($command->messageClass)][] = $command;
        }

        foreach ($routedCommandsByQueue as $queue => $routedCommands) {
            $this->senders[$queue]->send($queue, $routedCommands);
        }
    }
}
