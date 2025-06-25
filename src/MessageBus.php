<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Handler\CallDispatcher;
use Thesis\MessageBus\Handler\Context;
use Thesis\MessageBus\Handler\Endpoint;
use Thesis\MessageBus\Transport\Client;
use Thesis\MessageBus\Transport\Consumer;
use Thesis\MessageBus\Transport\Fake;
use Thesis\MessageBus\Transport\Publisher;
use Thesis\MessageBus\Transport\RoutedCommand;
use Thesis\MessageBus\Transport\Router;
use Thesis\MessageBus\Transport\Sender;

final readonly class MessageBus
{
    private CallDispatcher $callDispatcher;

    /**
     * @param array<non-empty-string, Handler<*>> $endpoints
     */
    public function __construct(
        private array $endpoints = [],
        private Sender $sender = Fake::Instance,
        private Publisher $publisher = Fake::Instance,
        private Consumer $consumer = Fake::Instance,
        private Client $client = Fake::Instance,
        private Router $router = new Router\Map(),
    ) {
        $this->callDispatcher = new CallDispatcher(
            endpoints: $this->endpoints,
            router: $this->router,
            client: $this->client,
        );
    }

    /**
     * @no-named-arguments
     * @param Command|Envelope<Command> ...$commands
     */
    public function send(Command|Envelope ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->sender->send(array_map(
            function (Command|Envelope $command): RoutedCommand {
                $command = Envelope::wrap($command);

                return new RoutedCommand(
                    endpoint: $this->router->route($command)
                        ?? throw new \LogicException(\sprintf('Failed to route `%s`', $command->messageClass)),
                    envelope: $command,
                );
            },
            $commands,
        ));
    }

    /**
     * @no-named-arguments
     * @param Event|Envelope<Event> ...$events
     */
    public function publish(Event|Envelope ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->publisher->publish(array_map(Envelope::wrap(...), $events));
    }

    /**
     * @template TResult
     * @param (Call<TResult>)|Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function invoke(Call|Envelope $call): mixed
    {
        $invoker = new Invoker($this->callDispatcher);
        $result = $invoker->invoke($call);

        $this->send(...$invoker->commands);
        $this->publish(...$invoker->events);

        return $result;
    }

    /**
     * @no-named-arguments
     * @param non-empty-string ...$endpoints
     */
    public function run(string ...$endpoints): void
    {
        foreach ($endpoints as $endpoint) {
            $handler = $this->endpoints[$endpoint];
            $context = new Context()->with(new Endpoint($endpoint));

            $this->consumer->consume($endpoint, function (Envelope $envelope) use ($handler, $context): void {
                $invoker = new Invoker($this->callDispatcher, $context);
                /** @phpstan-ignore argument.type */
                $result = $handler->handle($envelope, $context->with($invoker));
                $this->send(...$invoker->commands, ...$result->commands);
                $this->publish(...$invoker->events, ...$result->events);
            });
        }
    }
}
