<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface SubscriberTransport
{
    /**
     * @param non-empty-string $stream
     * @param non-empty-list<class-string> $eventClasses
     */
    public function subscribe(string $stream, array $eventClasses): void;

    /**
     * @param non-empty-string $stream
     * @param callable(non-empty-list<Envelope>): void $subscription
     */
    public function runSubscription(string $stream, callable $subscription): Run;
}
