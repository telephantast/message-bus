<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface Subscriber
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string> $eventClasses
     */
    public function subscribe(string $endpoint, array $eventClasses): void;
}
