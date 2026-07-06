<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface Subscriber
{
    /**
     * Brings the endpoint's subscriptions into exact correspondence with the given list:
     * missing subscriptions are added, subscriptions not on the list are removed.
     * An empty list unsubscribes the endpoint from everything.
     *
     * The list is treated as the single source of truth for the endpoint,
     * so an endpoint must not be shared by applications with different subscriptions.
     *
     * @param non-empty-string $endpoint
     * @param list<class-string> $eventClasses
     */
    public function subscribe(string $endpoint, array $eventClasses): void;
}
