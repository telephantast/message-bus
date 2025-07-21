<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\CommandDispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\EventDispatcher;
use Thesis\MessageBus\Internal\Queue;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Transport\Run;
use Thesis\MessageBus\Transport\Runs;

final readonly class MessageBus implements Sender, Publisher
{
    /**
     * @param array<non-empty-string, Queue<*>> $queues
     * @param array<non-empty-string, Subscription<*>> $subscriptions
     */
    public function __construct(
        private Endpoint $endpoint,
        private CommandDispatcher $commandDispatcher,
        private EventDispatcher $eventDispatcher,
        private EnvelopeFactory $envelopeFactory,
        private array $queues,
        private array $subscriptions,
    ) {}

    public function setup(): void
    {
        foreach ($this->queues as $queue) {
            $queue->setup();
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
     * @todo filter by endpoints
     */
    public function start(): Run
    {
        $runs = [];

        foreach ($this->queues as $queue) {
            $runs[] = $queue->start(
                envelopeFactory: $this->envelopeFactory,
                commandDispatcher: $this->commandDispatcher,
                eventDispatcher: $this->eventDispatcher,
            );
        }

        foreach ($this->subscriptions as $subscription) {
            $runs[] = $subscription->start(
                envelopeFactory: $this->envelopeFactory,
                commandDispatcher: $this->commandDispatcher,
                eventDispatcher: $this->eventDispatcher,
            );
        }

        return new Runs($runs);
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
