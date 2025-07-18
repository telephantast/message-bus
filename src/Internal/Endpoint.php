<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\MessageMatcher;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Run;
use Thesis\MessageBus\Transport\CallClient;
use Thesis\MessageBus\Transport\CallServer;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\EventReceiver;
use Thesis\MessageBus\Transport\Fake;

/**
 * @internal
 * @template TTransaction of object
 */
final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param Handlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        public string $name,
        private Handlers $handlers,
        private MessageMatcher $handlesCommand,
        private MessageMatcher $publishesEvent,
        private MessageMatcher $handlesCall,
        private Storage $storage,
        private EnvelopeFactory $envelopeFactory,
        private CommandSender $commandSender,
        private CommandReceiver $commandReceiver,
        private EventPublisher $eventPublisher,
        private EventReceiver $eventReceiver,
        private CallClient $callClient,
        private CallServer $callServer,
    ) {}

    /**
     * @param class-string<Command> $messageClass
     */
    public function handlesCommand(string $messageClass): bool
    {
        return $this->handlesCommand->matches($messageClass);
    }

    /**
     * @param class-string<Event> $messageClass
     */
    public function publishesEvent(string $messageClass): bool
    {
        return $this->publishesEvent->matches($messageClass);
    }

    /**
     * @param class-string<Call<*>> $messageClass
     */
    public function handlesCall(string $messageClass): bool
    {
        return $this->handlesCall->matches($messageClass);
    }

    public function setup(Dispatcher $dispatcher): void
    {
        $this->storage->setup();

        if ($this->handlers->events !== []) {
            $dispatcher->dispatchSubscription($this->name, $this->handlers->events);
        }
    }

    /**
     * @param non-empty-list<Envelope<Command>> $commands
     */
    public function send(array $commands): void
    {
        $this->commandSender->send($this->name, $commands);
    }

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @param ?Context<*> $parentContext
     * @return TResult
     */
    public function invoke(Envelope $call, Dispatcher $dispatcher, ?Context $parentContext = null): mixed
    {
        if ($this->callClient !== Fake::Instance) {
            return $this->callClient->invoke($this->name, $call);
        }

        if ($parentContext !== null && $parentContext->endpoint === $this->name) {
            /** @var Context<TTransaction> $parentContext */
            return $this->handlers->handleCall($call, new ChildContext(
                parent: $parentContext,
                envelopeFactory: $this->envelopeFactory,
                envelope: $call,
            ));
        }

        return RootContext::handleCall(
            endpoint: $this,
            storage: $this->storage,
            dispatcher: $dispatcher,
            envelopeFactory: $this->envelopeFactory,
            eventPublisher: $this->eventPublisher,
            handlers: $this->handlers,
            call: $call,
        );
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function subscribe(string $endpoint, array $toEvents): void
    {
        $this->eventPublisher->subscribe($endpoint, $toEvents);
    }

    /**
     * @param list<Run> $selector
     */
    public function run(Dispatcher $dispatcher, array $selector = []): void
    {
        foreach (array_unique($selector ?: Run::cases(), SORT_REGULAR) as $run) {
            match ($run) {
                Run::Commands => $this->commandReceiver->consumeCommands(
                    endpoint: $this->name,
                    consumer: function (Envelope $command) use ($dispatcher): void {
                        RootContext::handleCommandOrEvent(
                            endpoint: $this->name,
                            storage: $this->storage,
                            dispatcher: $dispatcher,
                            envelopeFactory: $this->envelopeFactory,
                            eventPublisher: $this->eventPublisher,
                            handler: $this->handlers->handleCommand(...),
                            envelope: $command,
                        );
                    },
                ),
                Run::Events => $this->eventReceiver->consumeEvents(
                    endpoint: $this->name,
                    consumer: function (Envelope $event) use ($dispatcher): void {
                        RootContext::handleCommandOrEvent(
                            endpoint: $this->name,
                            storage: $this->storage,
                            dispatcher: $dispatcher,
                            envelopeFactory: $this->envelopeFactory,
                            eventPublisher: $this->eventPublisher,
                            handler: $this->handlers->handleEvent(...),
                            envelope: $event,
                        );
                    },
                ),
                Run::Calls => $this->callServer->serve(
                    endpoint: $this->name,
                    handler: fn(Envelope $call): mixed => $this->invoke($call, $dispatcher),
                ),
            };
        }
    }
}
