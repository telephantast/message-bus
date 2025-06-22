<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Call\CallHandler;
use Thesis\MessageBus\Call\CallHandlers;
use Thesis\MessageBus\Command\CommandHandler;
use Thesis\MessageBus\Command\CommandHandlers;
use Thesis\MessageBus\Event\EventListener;
use Thesis\MessageBus\Event\EventListeners;

/**
 * @template-contravariant TCommands of object = never
 * @template-contravariant TCalls of Call = never
 * @extends Invoker<TCalls>
 */
final class MessageBus extends Invoker
{
    /**
     * @param CommandHandler<TCommands> $commandHandler
     * @param EventListener<object> $eventListener
     * @param CallHandler<TCalls> $callHandler
     */
    public function __construct(
        private readonly CommandHandler $commandHandler = new CommandHandlers(),
        private readonly EventListener $eventListener = new EventListeners(),
        private readonly CallHandler $callHandler = new CallHandlers(),
    ) {}

    public function send(object $command): void
    {
        if (!$command instanceof Envelope) {
            $command = new Envelope($command);
        }

        $this->processResult($this->commandHandler->handle($command, $this));
    }

    public function on(object $event): void
    {
        if (!$event instanceof Envelope) {
            $event = new Envelope($event);
        }

        $this->processResult($this->eventListener->on($event, $this));
    }

    protected function invokeEnvelope(Envelope $call): mixed
    {
        return $this->processResult($this->callHandler->handle($call, $this));
    }

    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return TResult
     */
    private function processResult(Result $result): mixed
    {
        foreach ($result->commandEnvelopes as $commandEnvelope) {
            $this->send($commandEnvelope);
        }

        foreach ($result->eventEnvelopes as $eventEnvelope) {
            $this->on($eventEnvelope);
        }

        return $result->result;
    }
}
