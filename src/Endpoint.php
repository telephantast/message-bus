<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\MessageClassMatcher\Boolean;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\EventReceiver;
use Thesis\MessageBus\Transport\Fake;

final readonly class Endpoint
{
    private CommandSender $commandSender;

    private CommandReceiver $commandReceiver;

    private EventPublisher $eventPublisher;

    private EventReceiver $eventReceiver;

    /**
     * @param non-empty-string $name
     * @param Handler<*> $handler
     */
    public function __construct(
        public string $name,
        private Handler $handler = new Handlers(),
        private MessageClassMatcher $handlesCommand = Boolean::False,
        private MessageClassMatcher $publishesEvent = Boolean::False,
        private MessageClassMatcher $handlesCall = Boolean::False,
        CommandSender|CommandReceiver|EventPublisher|EventReceiver $transport = Fake::Instance,
        ?CommandSender $commandSender = null,
        ?CommandReceiver $commandReceiver = null,
        ?EventPublisher $eventPublisher = null,
        ?EventReceiver $eventReceiver = null,
    ) {
        $this->commandSender = $commandSender ?? ($transport instanceof CommandSender ? $transport : Fake::Instance);
        $this->commandReceiver = $commandReceiver ?? ($transport instanceof CommandReceiver ? $transport : Fake::Instance);
        $this->eventPublisher = $eventPublisher ?? ($transport instanceof EventPublisher ? $transport : Fake::Instance);
        $this->eventReceiver = $eventReceiver ?? ($transport instanceof EventReceiver ? $transport : Fake::Instance);
    }

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

    public function setup(MessageBus $messageBus): void
    {
        $events = array_filter(
            $this->handler->messageClasses,
            static fn(string $messageClass): bool => is_a($messageClass, Event::class, allow_string: true),
        );

        if ($events !== []) {
            $messageBus->subscribe($this->name, array_values($events));
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
    public function invoke(Envelope $call, Context $context): mixed
    {
        // todo send via transport if cannot handle here
        return $this->doHandle($call, $context);
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function subscribe(string $endpoint, array $toEvents): void
    {
        $this->eventPublisher->subscribe($endpoint, $toEvents);
    }

    public function run(Context $context): void
    {
        $consumer = fn(Envelope $envelope): mixed => $this->doHandle($envelope, $context);

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
    private function doHandle(Envelope $envelope, Context $context): mixed
    {
        $publisher = new CollectingPublisher();
        $context = $context->with($publisher, Publisher::class);

        /** @phpstan-ignore argument.type */
        $result = $this->handler->handle($this->name, $envelope, $context);

        if ($publisher->events !== []) {
            $this->eventPublisher->publish($this->name, $publisher->events);
            $publisher->clear();
        }

        return $result;
    }
}
