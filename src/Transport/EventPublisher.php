<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface EventPublisher
{
    /**
     * @param non-empty-string $subscription
     * @param list<class-string> $eventClasses
     */
    public function subscribe(string $subscription, array $eventClasses): void;

    /**
     * @param non-empty-list<Envelope> $events
     */
    public function publish(array $events): void;

    /**
     * @param non-empty-string $subscription
     * @param callable(Envelope): void $handler
     */
    public function startSubscription(string $subscription, callable $handler): Run;
}
