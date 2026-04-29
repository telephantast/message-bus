<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface Receiver
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope): Disposition $handler
     */
    public function startConsumer(string $endpoint, callable $handler): Consumer;
}
