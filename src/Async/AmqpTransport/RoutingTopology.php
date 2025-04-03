<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Message\Message;

/**
 * @api
 */
interface RoutingTopology
{
    /**
     * @param class-string<Message> $messageClass
     * @return non-empty-string
     */
    public function resolveExchange(string $messageClass): string;
}
