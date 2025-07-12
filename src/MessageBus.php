<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Handler\Context;

/**
 * @implements Invoker<Call>
 */
final readonly class MessageBus implements Sender, Invoker
{
    /**
     * @var array<non-empty-string, Endpoint>
     */
    private array $endpoints;

    /**
     * @param list<Endpoint> $endpoints
     */
    public function __construct(array $endpoints = [])
    {
        $this->endpoints = array_column($endpoints, null, 'name');
    }

    public function setup(): void
    {
        foreach ($this->endpoints as $endpoint) {
            $endpoint->setup($this);
        }
    }

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function subscribe(string $endpoint, array $toEvents): void
    {
        foreach ($toEvents as $toEvent) {
            foreach ($this->endpoints as $eachEndpoint) {
                if ($eachEndpoint->publishesEvent($toEvent)) {
                    $eachEndpoint->subscribe($endpoint, [$toEvent]);

                    continue 2;
                }
            }

            throw new \LogicException(\sprintf('Publisher of `%s` not found', $toEvent));
        }
    }

    /**
     * @no-named-arguments
     * @param Command|Envelope<Command> ...$commands
     */
    public function send(Command|Envelope ...$commands): void
    {
        foreach ($commands as $command) {
            $command = Envelope::wrap($command);

            foreach ($this->endpoints as $endpoint) {
                if ($endpoint->handlesCommand($command->messageClass)) {
                    $endpoint->send([$command]);

                    continue 2;
                }
            }

            throw new \LogicException(\sprintf('Failed to route command `%s`', $command->messageClass));
        }
    }

    public function invoke(Call|Envelope $call, Context $context = new Context()): mixed
    {
        $call = Envelope::wrap($call);

        if (!$context->has(Sender::class)) {
            $context = $context->with($this, Sender::class);
        }

        if (!$context->has(Invoker::class)) {
            $context = $context->with($this, Sender::class);
        }

        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->handlesCall($call->messageClass)) {
                return $endpoint->invoke($call, $context);
            }
        }

        throw new \LogicException(\sprintf('Failed to route call `%s`', $call->messageClass));
    }

    /**
     * @no-named-arguments
     * @param non-empty-string ...$endpoints
     */
    public function run(string ...$endpoints): void
    {
        $context = new Context()->with($this, Sender::class, Invoker::class);

        foreach ($endpoints as $name) {
            $this->endpoint($name)->run($context);
        }
    }

    /**
     * @param non-empty-string $name
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new \LogicException();
    }
}
