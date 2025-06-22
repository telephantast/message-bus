<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Command;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;
use function Typhoon\Formatter\formatClass;

/**
 * @template-contravariant TCommands of object = never
 * @implements CommandHandler<TCommands>
 */
final class CommandHandlers implements CommandHandler
{
    /**
     * @var array<class-string, CommandHandler<*>>
     */
    private(set) public array $handlers = [];

    /**
     * @template TCommand of object
     * @param non-empty-list<class-string<TCommand>> $calls
     * @param CommandHandler<TCommand> $handler
     * @return self<TCommands|TCommand>
     */
    public function with(array $calls, CommandHandler $handler): self
    {
        $copy = clone $this;

        foreach ($calls as $call) {
            if (isset($copy->handlers[$call])) {
                throw new \LogicException(\sprintf(
                    'Handler for command `%s` already exists: `%s`',
                    $call,
                    formatClass($handler),
                ));
            }

            $copy->handlers[$call] = $handler;
        }

        return $copy;
    }

    public function handle(Envelope $command, Invoker $invoker): Result
    {
        $handler = $this->handlers[$command->messageClass]
            ?? throw new \LogicException(\sprintf('No handler for command `%s`', $command->messageClass));

        /** @phpstan-ignore argument.type */
        return $handler->handle($command, $invoker);
    }
}
