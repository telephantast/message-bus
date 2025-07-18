<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\CommandDispatcher;
use Thesis\MessageBus\Internal\CommandEndpoint;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\EventDispatcher;
use Thesis\MessageBus\Internal\Router;
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
    private string $endpoint = 'message_bus';

    /**
     * @param non-empty-string|Name $name
     */
    public function messageBusEndpointName(string|Name $name): self
    {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        $this->endpoint = $name;

        return $this;
    }

    /**
     * @var array<non-empty-string, CommandEndpoint<*>>
     */
    private array $commandEndpoints = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string|Name $name
     * @param CommandHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function localCommandEndpoint(
        string|Name $name,
        CommandHandlers $handlers,
        Storage $storage,
        CommandReceiver $receiver,
        null|false|CommandSender $sender = null,
    ): self {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        $this->commandEndpoints[$name] = new CommandEndpoint($name, $handlers, $receiver, $storage);

        if ($handlers->commandClasses !== []) {
            if ($sender instanceof CommandSender) {
                $this->remoteCommandEndpoint($name, new AnyOf($handlers->commandClasses), $sender);
            } elseif ($sender === null && $receiver instanceof CommandSender) {
                $this->remoteCommandEndpoint($name, new AnyOf($handlers->commandClasses), $receiver);
            }
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
    private array $commandSenders = [];

    /**
     * @param non-empty-string|Name $name
     */
    public function remoteCommandEndpoint(string|Name $name, MessageMatcher $commands, CommandSender $sender): self
    {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        $this->commandMatchers[$name] = $commands;
        $this->commandSenders[$name] = $sender;

        return $this;
    }

    /**
     * @var array<non-empty-string, EventPublisher>
     */
    private array $eventPublishers = [];

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $eventMatchers = [];

    /**
     * @param non-empty-string|Name $name
     */
    public function eventPublisher(string|Name $name, MessageMatcher $events, EventPublisher $publisher): self
    {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        $this->eventMatchers[$name] = $events;
        $this->eventPublishers[$name] = $publisher;

        return $this;
    }

    /**
     * @var array<non-empty-string, array{EventListeners<*>, Storage<*>}>
     */
    private array $subscriptions = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string|Name $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     */
    public function eventSubscription(string|Name $name, EventListeners $listeners, Storage $storage): self
    {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        $this->subscriptions[$name] = [$listeners, $storage];

        return $this;
    }

    public function build(): MessageBus
    {
        $eventRouter = new Router($this->eventMatchers);

        $subscriptions = [];

        foreach ($this->subscriptions as $name => [$listeners, $storage]) {
            $publisher = null;

            foreach ($listeners->eventClasses as $eventClass) {
                $matchedPublisher = $this->eventPublishers[$eventRouter->route($eventClass)];

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
            );
        }

        return new MessageBus(
            endpoint: $this->endpoint,
            commandDispatcher: new CommandDispatcher(
                router: new Router($this->commandMatchers),
                senders: $this->commandSenders,
            ),
            eventDispatcher: new EventDispatcher(
                router: new Router($this->eventMatchers),
                publishers: $this->eventPublishers,
            ),
            envelopeFactory: new EnvelopeFactory(),
            commandEndpoints: $this->commandEndpoints,
            subscriptions: $subscriptions,
        );
    }
}
