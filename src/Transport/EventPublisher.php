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
     * @param callable(non-empty-list<Envelope>): void $handler
     * @param positive-int $maxBatchSize
     */
    public function startSubscription(string $subscription, callable $handler, int $maxBatchSize = 1): Run;
}
