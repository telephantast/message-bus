<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\Queue;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Transport\Run;
use Thesis\MessageBus\Transport\Runs;

/**
 * @implements Invoker<object>
 */
final readonly class MessageBus implements Sender, Publisher, Invoker
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, Queue<*>> $queues
     * @param array<non-empty-string, Subscription<*>> $subscriptions
     */
    public function __construct(
        string $name,
        private Dispatcher $dispatcher,
        private EnvelopeFactory $envelopeFactory,
        private array $queues,
        private array $subscriptions,
    ) {
        $this->endpoint = Endpoint::service($name);
    }

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

        $this->dispatcher->send(
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

        $this->dispatcher->publish(
            array_map(
                $this->createEnvelope(...),
                $events,
            ),
        );
    }

    public function invoke(object $call): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($call), $this->envelopeFactory);
    }

    /**
     * @todo filter by endpoints
     */
    public function start(): Run
    {
        $runs = [];

        foreach ($this->queues as $queue) {
            $runs[] = $queue->start($this->envelopeFactory, $this->dispatcher);
        }

        foreach ($this->subscriptions as $subscription) {
            $runs[] = $subscription->start($this->envelopeFactory, $this->dispatcher);
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
