<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Call;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\MessageMatcher;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\EventReceiver;

/**
 * @internal
 */
final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param Handler<*> $handler
     */
    public function __construct(
        public string $name,
        private Handler $handler,
        private MessageMatcher $handlesCommand,
        private MessageMatcher $publishesEvent,
        private MessageMatcher $handlesCall,
        private Storage $storage,
        private OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        private CommandSender $commandSender,
        private CommandReceiver $commandReceiver,
        private EventPublisher $eventPublisher,
        private EventReceiver $eventReceiver,
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

        $events = array_filter(
            $this->handler->messageClasses,
            static fn(string $messageClass): bool => is_a($messageClass, Event::class, allow_string: true),
        );

        if ($events !== []) {
            $dispatcher->dispatchSubscription($this->name, array_values($events));
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
     * @return TResult
     */
    public function invoke(Envelope $call, Dispatcher $dispatcher, ?Context $parentContext): mixed
    {
        // todo send via transport if cannot handle here
        return $this->doHandle($call, $dispatcher, $parentContext);
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function subscribe(string $endpoint, array $toEvents): void
    {
        $this->eventPublisher->subscribe($endpoint, $toEvents);
    }

    public function run(Dispatcher $dispatcher): void
    {
        $consumer = fn(Envelope $envelope): mixed => $this->doHandle($envelope, $dispatcher);

        /** @phpstan-ignore argument.type */
        $this->commandReceiver->consumeCommands($this->name, $consumer);
        /** @phpstan-ignore argument.type */
        $this->eventReceiver->consumeEvents($this->name, $consumer);
    }

    /**
     * @template TResult
     * @param Envelope<Message<TResult>> $envelope
     * @return TResult
     */
    private function doHandle(Envelope $envelope, Dispatcher $dispatcher, ?Context $parentContext = null): mixed
    {
        if ($parentContext !== null && $parentContext->endpoint === $this->name) {
            return $this->handler->handle(
                /** @phpstan-ignore argument.type */
                envelope: $envelope,
                context: new ChildContext(
                    parent: $parentContext,
                    outgoingEnvelopeProcessor: $this->outgoingEnvelopeProcessor,
                    envelope: $envelope,
                ),
            );
        }

        return RootContext::handle(
            endpoint: $this->name,
            storage: $this->storage,
            dispatcher: $dispatcher,
            outgoingEnvelopeProcessor: $this->outgoingEnvelopeProcessor,
            eventPublisher: $this->eventPublisher,
            handler: $this->handler, /** @phpstan-ignore argument.type */
            envelope: $envelope,
        );
    }
}
