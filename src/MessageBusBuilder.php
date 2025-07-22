<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Handler\CallHandlers;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\Queue;
use Thesis\MessageBus\Internal\Router;
use Thesis\MessageBus\Internal\Service;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\MessageMatcher\AnyOf;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;

final class MessageBusBuilder
{
    /**
     * @var non-empty-string
     */
    private string $name = 'message_bus';

    /**
     * @param non-empty-string $name
     */
    public function endpointName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @var array<non-empty-string, Queue<*>>
     */
    private array $queues = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     * @param positive-int $maxBatchSize
     */
    public function queue(
        string $name,
        CommandHandlers $handlers,
        Storage $storage,
        CommandReceiver $receiver,
        ?CommandSender $sender = null,
        int $maxBatchSize = 1,
    ): self {
        $this->queues[$name] = new Queue($name, $handlers, $receiver, $storage, $maxBatchSize);

        if ($sender !== null && $handlers->commandClasses !== []) {
            $this->remoteQueue($name, new AnyOf($handlers->commandClasses), $sender);
        }

        return $this;
    }

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $commandMatchers = [];

    /**
     * @var array<non-empty-string, CommandSender>
     */
    private array $senders = [];

    /**
     * @param non-empty-string $name
     */
    public function remoteQueue(string $name, MessageMatcher $commands, CommandSender $sender): self
    {
        $this->commandMatchers[$name] = $commands;
        $this->senders[$name] = $sender;

        return $this;
    }

    /**
     * @var list<EventPublisher>
     */
    private array $publishers = [];

    /**
     * @var list<MessageMatcher>
     */
    private array $eventMatchers = [];

    public function publisher(MessageMatcher $events, EventPublisher $publisher): self
    {
        $this->eventMatchers[] = $events;
        $this->publishers[] = $publisher;

        return $this;
    }

    /**
     * @var array<non-empty-string, array{EventListeners<*>, Storage<*>, positive-int}>
     */
    private array $subscriptions = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     * @param positive-int $maxBatchSize
     */
    public function subscription(string $name, EventListeners $listeners, Storage $storage, int $maxBatchSize = 1): self
    {
        $this->subscriptions[$name] = [$listeners, $storage, $maxBatchSize];

        return $this;
    }

    /**
     * @var array<non-empty-string, Service<*>>
     */
    private array $services = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param CallHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function service(
        string $name,
        CallHandlers $handlers,
        Storage $storage,
    ): self {
        $this->services[$name] = new Service(
            name: $name,
            handlers: $handlers,
            storage: $storage,
        );

        if ($handlers->callClasses !== []) {
            $this->callMatchers[$name] = new AnyOf($handlers->callClasses);
        }

        // todo

        return $this;
    }

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $callMatchers = [];

    public function build(): MessageBus
    {
        $eventRouter = new Router($this->eventMatchers);

        return new MessageBus(
            name: $this->name,
            dispatcher: new Dispatcher(
                commandRouter: new Router($this->commandMatchers),
                senders: $this->senders,
                eventRouter: $eventRouter,
                publishers: $this->publishers,
                callRouter: new Router($this->callMatchers),
                services: $this->services,
            ),
            envelopeFactory: new EnvelopeFactory(),
            queues: $this->queues,
            subscriptions: $this->buildSubscriptions($eventRouter),
        );
    }

    /**
     * @param Router<non-negative-int> $eventRouter
     * @return array<non-empty-string, Subscription<*>>
     */
    private function buildSubscriptions(Router $eventRouter): array
    {
        $subscriptions = [];

        foreach ($this->subscriptions as $name => [$listeners, $storage, $maxBatchSize]) {
            $publisher = null;

            foreach ($listeners->eventClasses as $eventClass) {
                $matchedPublisher = $this->publishers[$eventRouter->route($eventClass)];

                if ($publisher !== null && $publisher !== $matchedPublisher) {
                    throw new \LogicException();
                }

                $publisher = $matchedPublisher;
            }

            \assert($publisher !== null);

            $subscriptions[$name] = new Subscription(
                name: $name,
                /** @phpstan-ignore argument.type */
                listeners: $listeners,
                publisher: $publisher,
                /** @phpstan-ignore argument.type */
                storage: $storage,
                maxBatchSize: $maxBatchSize,
            );
        }

        return $subscriptions;
    }
}
