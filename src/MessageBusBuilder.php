<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Internal\Consumer;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\Router;
use Thesis\MessageBus\Internal\Service;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Internal\Wrapper;
use Thesis\MessageBus\MessageMatcher\AnyOf;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\ConsumerTransport;
use Thesis\MessageBus\Transport\ProducerTransport;
use Thesis\MessageBus\Transport\PublisherTransport;

final class MessageBusBuilder
{
    /**
     * @var array<non-empty-string, Consumer<*>>
     */
    private array $consumers = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function consumer(
        string $name,
        CommandHandlers $handlers,
        Storage $storage,
        ConsumerTransport $transport,
        ?string $persistenceKey = null,
    ): self {
        // todo resolve via types
        \assert($handlers->commandClasses !== []);

        $this->consumers[$name] = new Consumer(
            name: $name,
            handlers: $handlers,
            receiver: $transport,
            storage: $storage,
            persistenceKey: $persistenceKey ?? spl_object_hash($storage),
            wrapper: new Wrapper(), // todo
        );
        $this->remoteConsumer($name, new AnyOf($handlers->commandClasses), $transport);

        return $this;
    }

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $commandMatchers = [];

    /**
     * @var array<non-empty-string, ProducerTransport>
     */
    private array $producers = [];

    /**
     * @param non-empty-string $name
     */
    public function remoteConsumer(string $name, MessageMatcher $commands, ProducerTransport $transport): self
    {
        $this->commandMatchers[$name] = $commands;
        $this->producers[$name] = $transport;

        return $this;
    }

    /**
     * @var list<PublisherTransport>
     */
    private array $publishers = [];

    /**
     * @var list<MessageMatcher>
     */
    private array $eventMatchers = [];

    public function publisher(MessageMatcher $events, PublisherTransport $transport): self
    {
        $this->eventMatchers[] = $events;
        $this->publishers[] = $transport;

        return $this;
    }

    /**
     * @var array<non-empty-string, array{EventListeners<*>, Storage<*>, string}>
     */
    private array $subscriptions = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param EventListeners<TTransaction> $listeners
     * @param Storage<TTransaction> $storage
     */
    public function subscription(
        string $name,
        EventListeners $listeners,
        Storage $storage,
        ?string $persistenceKey = null,
    ): self {
        $this->subscriptions[$name] = [
            $listeners,
            $storage,
            $persistenceKey ?? spl_object_hash($storage),
        ];

        return $this;
    }

    /**
     * @var array<non-empty-string, Service<*>>
     */
    private array $services = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param MethodHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function service(
        string $name,
        MethodHandlers $handlers,
        Storage $storage,
        ?string $persistenceKey = null,
    ): self {
        $this->services[$name] = new Service(
            name: $name,
            handlers: $handlers,
            wrapper: new Wrapper(),
            storage: $storage, // todo
            persistenceKey: $persistenceKey ?? spl_object_hash($storage),
        );

        if ($handlers->methodClasses !== []) {
            $this->methodMatchers[$name] = new AnyOf($handlers->methodClasses);
        }

        // todo

        return $this;
    }

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $methodMatchers = [];

    public function build(): MessageBus
    {
        $eventRouter = new Router($this->eventMatchers);

        return new MessageBus(
            wrapper: new Wrapper(),
            dispatcher: new Dispatcher(
                commandRouter: new Router($this->commandMatchers),
                producers: $this->producers,
                eventRouter: $eventRouter,
                publishers: $this->publishers,
                methodRouter: new Router($this->methodMatchers),
                services: $this->services,
            ),
            consumers: $this->consumers,
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

        foreach ($this->subscriptions as $name => [$listeners, $storage, $persistenceKey]) {
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
                persistenceKey: $persistenceKey,
                wrapper: new Wrapper(), // todo
            );
        }

        return $subscriptions;
    }
}
