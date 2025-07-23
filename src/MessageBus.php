<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Internal\Consumer;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\Subscription;
use Thesis\MessageBus\Internal\Wrapper;
use Thesis\MessageBus\Transport\Run;
use Thesis\MessageBus\Transport\Runs;

/**
 * @template-contravariant TSupportedMethods of object = object
 * @implements Invoke<TSupportedMethods>
 */
final readonly class MessageBus implements Invoke
{
    /**
     * @param array<non-empty-string, Consumer<*>> $consumers
     * @param array<non-empty-string, Subscription<*>> $subscriptions
     */
    public function __construct(
        private Wrapper $wrapper,
        private Dispatcher $dispatcher,
        private array $consumers,
        private array $subscriptions,
    ) {}

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

        $this->dispatcher->send(array_map($this->wrapper->wrap(...), $commands));
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->dispatcher->publish(array_map($this->wrapper->wrap(...), $events));
    }

    /**
     * @template TResult
     * @param TSupportedMethods|Envelope<TSupportedMethods> $method
     * @return ($method is (Method<TResult>|Envelope<Method<TResult>>) ? TResult : mixed)
     */
    public function invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->wrapper->wrap($method));
    }

    public function __invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->wrapper->wrap($method));
    }

    /**
     * @todo filter by endpoints
     */
    public function run(): Run
    {
        $runs = [];

        foreach ($this->consumers as $consumer) {
            $runs[] = $consumer->run($this->dispatcher);
        }

        foreach ($this->subscriptions as $subscription) {
            $runs[] = $subscription->run($this->dispatcher);
        }

        return new Runs($runs);
    }
}
