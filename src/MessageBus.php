<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\CommandDispatcher;
use Thesis\MessageBus\Internal\CommandEndpoint;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\EventDispatcher;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Transport\Canceller;

final readonly class MessageBus implements Sender, Publisher
{
    /**
     * @param non-empty-string $endpoint
     * @param array<non-empty-string, CommandEndpoint<*>> $commandEndpoints
     * @param array<non-empty-string, Subscription<*>> $subscriptions
     */
    public function __construct(
        private string $endpoint,
        private CommandDispatcher $commandDispatcher,
        private EventDispatcher $eventDispatcher,
        private EnvelopeFactory $envelopeFactory,
        private array $commandEndpoints,
        private array $subscriptions,
    ) {}

    public function setup(): void
    {
        foreach ($this->commandEndpoints as $commandEndpoint) {
            $commandEndpoint->setup();
        }

        foreach ($this->subscriptions as $subscription) {
            $subscription->setup();
        }
    }

    public function send(object ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->commandDispatcher->send(
            array_map(
                $this->createEnvelope(...),
                $commands,
            ),
        );
    }

    public function publish(object ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->eventDispatcher->publish(
            array_map(
                $this->createEnvelope(...),
                $events,
            ),
        );
    }

    /**
     * @param non-empty-string|Name $endpoint
     */
    public function startCommandConsumer(string|Name $endpoint): Canceller
    {
        if ($endpoint instanceof Name) {
            $endpoint = $endpoint->toString();
        }

        return ($this->commandEndpoints[$endpoint] ?? throw new \LogicException())->startConsumer(
            envelopeFactory: $this->envelopeFactory,
            commandDispatcher: $this->commandDispatcher,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    /**
     * @param non-empty-string|Name $name
     */
    public function startSubscription(string|Name $name): Canceller
    {
        if ($name instanceof Name) {
            $name = $name->toString();
        }

        return ($this->subscriptions[$name] ?? throw new \LogicException())->start(
            envelopeFactory: $this->envelopeFactory,
            commandDispatcher: $this->commandDispatcher,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    /**
     * @template TMessage of object
     * @param TMessage|Envelope<TMessage> $message
     * @return Envelope<TMessage>
     */
    private function createEnvelope(object $message): Envelope
    {
        return $this->envelopeFactory->create($this->endpoint, $message);
    }
}
