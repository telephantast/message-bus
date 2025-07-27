<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Psr\Clock\ClockInterface;
use Thesis\MessageBus\Envelope\EnvelopeProcessor;
use Thesis\MessageBus\Envelope\MessageIdGenerator;
use Thesis\MessageBus\Handler\CommandHandlers;
use Thesis\MessageBus\Handler\EventListeners;
use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Internal\Consumer;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\Router;
use Thesis\MessageBus\Internal\Service;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Internal\SubscriptionFactory;
use Thesis\MessageBus\Internal\Wrapper;
use Thesis\MessageBus\MessageMatcher\AnyOf;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\ConsumerTransport;
use Thesis\MessageBus\Transport\ProducerTransport;
use Thesis\MessageBus\Transport\PublisherTransport;

final class MessageBusBuilder
{
    private Wrapper $wrapper;

    public function __construct()
    {
        $this->wrapper = new Wrapper();
    }

    public function messageIdGenerator(MessageIdGenerator $messageIdGenerator): self
    {
        $this->wrapper = $this->wrapper->withMessageIdGenerator($messageIdGenerator);

        return $this;
    }

    public function clock(?ClockInterface $clock): self
    {
        $this->wrapper = $this->wrapper->withClock($clock);

        return $this;
    }

    public function envelopeProcessor(EnvelopeProcessor $processor): self
    {
        $this->wrapper = $this->wrapper->withProcessor($processor);

        return $this;
    }

    /**
     * @var array<non-empty-string, Consumer<*>>
     */
    private array $consumers = [];

    /**
     * @template TTransaction of object
     * @param non-empty-string $name
     * @param CommandHandlers<TTransaction, true> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function consumer(
        string $name,
        CommandHandlers $handlers,
        Storage $storage,
        ConsumerTransport $transport,
        ?string $transactionKey = null,
    ): self {
        $this->consumers[$name] = new Consumer(
            name: $name,
            handlers: $handlers,
            receiver: $transport,
            storage: $storage,
            transactionKey: $transactionKey ?? spl_object_hash($storage),
            wrapper: new Wrapper(), // todo
        );
        $this->commandMatchers[$name] = new AnyOf($handlers->commandClasses);
        $this->producerTransports[$name] = $transport;

        return $this;
    }

    /**
     * @var array<non-empty-string, MessageMatcher>
     */
    private array $commandMatchers = [];

    /**
     * @var array<non-empty-string, ProducerTransport>
     */
    private array $producerTransports = [];

    /**
     * @param non-empty-string $name
     */
    public function remoteConsumer(string $name, MessageMatcher $commands, ProducerTransport $transport): self
    {
        $this->commandMatchers[$name] = $commands;
        $this->producerTransports[$name] = $transport;

        return $this;
    }

    /**
     * @var list<PublisherTransport>
     */
    private array $publisherTransports = [];

    /**
     * @var list<MessageMatcher>
     */
    private array $eventMatchers = [];

    public function publisher(MessageMatcher $events, PublisherTransport $transport): self
    {
        $this->eventMatchers[] = $events;
        $this->publisherTransports[] = $transport;

        return $this;
    }

    /**
     * @var array<non-empty-string, SubscriptionFactory<*>>
     */
    private array $subscriptionFactories = [];

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
        ?string $transactionKey = null,
    ): self {
        $this->subscriptionFactories[$name] = new SubscriptionFactory(
            name: $name,
            listeners: $listeners,
            storage: $storage,
            transactionKey: $transactionKey ?? spl_object_hash($storage),
        );

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
        ?string $transactionKey = null,
    ): self {
        $this->services[$name] = new Service(
            name: $name,
            handlers: $handlers,
            wrapper: new Wrapper(),
            storage: $storage, // todo
            transactionKey: $transactionKey ?? spl_object_hash($storage),
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
            wrapper: $this->wrapper,
            dispatcher: new Dispatcher(
                commandRouter: new Router($this->commandMatchers),
                producerTransports: $this->producerTransports,
                eventRouter: $eventRouter,
                publisherTransports: $this->publisherTransports,
                methodRouter: new Router($this->methodMatchers),
                services: $this->services,
            ),
            consumers: $this->consumers,
            subscriptions: array_map(
                fn(SubscriptionFactory $factory): Subscription => $factory->build(
                    wrapper: $this->wrapper,
                    eventRouter: $eventRouter,
                    publisherTransports: $this->publisherTransports,
                ),
                $this->subscriptionFactories,
            ),
        );
    }
}
