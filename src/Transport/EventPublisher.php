<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface EventPublisher
{
    /**
     * @param non-empty-string $subscriptionName
     * @param list<class-string> $toEventClasses
     */
    public function subscribe(string $subscriptionName, array $toEventClasses): void;

    /**
     * @param non-empty-string $subscriptionName
     * @param callable(Envelope<object>): void $handler
     */
    public function startSubscription(string $subscriptionName, callable $handler): Run;

    /**
     * @param non-empty-list<Envelope<object>> $events
     */
    public function publish(array $events): void;
}
