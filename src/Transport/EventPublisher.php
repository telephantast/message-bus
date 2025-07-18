<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface EventPublisher
{
    /**
     * @param non-empty-string $subscription
     * @param list<class-string> $toEventClasses
     */
    public function subscribe(string $subscription, array $toEventClasses): void;

    /**
     * @param non-empty-string $subscription
     * @param callable(Envelope<object>): void $handler
     */
    public function startSubscription(string $subscription, callable $handler): Canceller;

    /**
     * @param non-empty-string $publisher
     * @param non-empty-list<Envelope<object>> $events
     */
    public function publish(string $publisher, array $events): void;
}
