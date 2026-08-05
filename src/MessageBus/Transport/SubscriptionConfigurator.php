<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface SubscriptionConfigurator
{
    /**
     * @param non-empty-string $queue
     * @param list<non-empty-string> $messageTypes
     */
    public function subscribe(string $queue, array $messageTypes): void;
}
