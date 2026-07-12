<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\CommandRouter;
use Thesis\MessageBus\ConsumerRuntime;
use Thesis\MessageBus\ConsumerRuntime\Consumer;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\EndpointConfig;
use Thesis\MessageBus\Event;
use Thesis\MessageBus\Exception\NoEndpoint;
use Thesis\MessageBus\Internal\AttributeCommandRouter;
use Thesis\MessageBus\Internal\Endpoint;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\Router;
use Thesis\MessageBus\Metadata\IdGenerator;
use Thesis\MessageBus\Subscriber;

/**
 * @api
 *
 * @template-covariant Tx of object = object
 */
final readonly class MessageBus
{
    /**
     * @template BTx of object
     * @param ConsumerRuntime<BTx> $consumerRuntime
     * @param list<EndpointConfig<BTx>> $endpoints
     * @param list<CommandRouter> $commandRouters
     * @param non-empty-string $name
     * @return self<BTx>
     */
    public static function build(
        Subscriber $subscriber,
        Dispatcher $dispatcher,
        ConsumerRuntime $consumerRuntime,
        array $endpoints,
        array $commandRouters = [],
        IdGenerator $idGenerator = new IdGenerator\UuidV7(),
        string $name = 'message_bus',
    ): self {
        $router = new Router(
            new CommandRouter\Chain([
                ...$commandRouters,
                new AttributeCommandRouter(),
                self::endpointCommandRouter($endpoints),
            ]),
        );

        return new self(
            subscriber: $subscriber,
            dispatcher: $dispatcher,
            envelopeFactory: new EnvelopeFactory(
                origin: $name,
                idGenerator: $idGenerator,
                router: $router,
            ),
            endpoints: array_combine(
                array_column($endpoints, 'name'),
                array_map(
                    static fn(EndpointConfig $config) => new Endpoint(
                        name: $config->name,
                        handlers: $config->handlers,
                        listeners: $config->listeners,
                        consumerRuntime: $consumerRuntime,
                        envelopeFactory: new EnvelopeFactory(
                            origin: $config->name,
                            idGenerator: $idGenerator,
                            router: $router,
                        ),
                    ),
                    $endpoints,
                ),
            ),
        );
    }

    /**
     * @param list<EndpointConfig<*>> $endpoints
     */
    private static function endpointCommandRouter(array $endpoints): CommandRouter\Map
    {
        $endpointRoutes = [];

        foreach ($endpoints as $endpoint) {
            foreach ($endpoint->handlers->payloadClasses as $payloadClass) {
                $endpointRoutes[$payloadClass] = $endpoint->name;
            }
        }

        return new CommandRouter\Map($endpointRoutes);
    }

    /**
     * @param array<non-empty-string, Endpoint<Tx>> $endpoints
     */
    private function __construct(
        private Subscriber $subscriber,
        private Dispatcher $dispatcher,
        private EnvelopeFactory $envelopeFactory,
        private array $endpoints,
    ) {}

    public function subscribe(): void
    {
        foreach ($this->endpoints as $name => $endpoint) {
            $this->subscriber->subscribe($name, $endpoint->subscribedTo);
        }
    }

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map(
            fn(object $command) => $this->envelopeFactory->buildOutgoing(Command::from($command)),
            $commands,
        ));
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map(
            fn(object $event) => $this->envelopeFactory->buildOutgoing(Event::from($event)),
            $events,
        ));
    }

    /**
     * @param non-empty-string $endpoint
     */
    public function consumeCommand(string $endpoint, object $command): void
    {
        $this->endpoint($endpoint)->consume($this->envelopeFactory->build(Command::from($command)));
    }

    /**
     * @param non-empty-string $endpoint
     */
    public function consumeEvent(string $endpoint, object $event): void
    {
        $this->endpoint($endpoint)->consume($this->envelopeFactory->build(Event::from($event)));
    }

    /**
     * @param non-empty-string $endpoint
     */
    public function startConsumer(string $endpoint): Consumer
    {
        return $this->endpoint($endpoint)->startConsumer();
    }

    /**
     * @param non-empty-string $name
     * @return Endpoint<Tx>
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new NoEndpoint($name);
    }
}
