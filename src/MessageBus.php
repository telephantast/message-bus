<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\Consumer;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Transport\Run;
use Thesis\MessageBus\Transport\Runs;

/**
 * @template-contravariant TSupportedMethods of object = object
 * @implements Invoke<TSupportedMethods>
 */
final readonly class MessageBus implements Invoke
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, Consumer<*>> $consumers
     * @param array<non-empty-string, Subscription<*>> $subscriptions
     */
    public function __construct(
        string $name,
        private Dispatcher $dispatcher,
        private EnvelopeFactory $envelopeFactory,
        private array $consumers,
        private array $subscriptions,
    ) {
        $this->endpoint = Endpoint::service($name);
    }

    public function setup(): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->setup();
        }

        foreach ($this->subscriptions as $subscription) {
            $subscription->setup();
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

        $this->dispatcher->send(
            array_map(
                $this->createEnvelope(...),
                $commands,
            ),
        );
    }

    /**
     * @no-named-arguments
     */
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

    /**
     * @template TResult
     * @param TSupportedMethods|Envelope<TSupportedMethods> $method
     * @return ($method is (Method<TResult>|Envelope<Method<TResult>>) ? TResult : mixed)
     */
    public function invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($method), $this->envelopeFactory);
    }

    public function __invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($method), $this->envelopeFactory);
    }

    /**
     * @todo filter by endpoints
     */
    public function run(): Run
    {
        $runs = [];

        foreach ($this->consumers as $consumer) {
            $runs[] = $consumer->run($this->envelopeFactory, $this->dispatcher);
        }

        foreach ($this->subscriptions as $subscription) {
            $runs[] = $subscription->run($this->envelopeFactory, $this->dispatcher);
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
