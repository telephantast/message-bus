<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface Receiver
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(InboundEnvelope): Disposition $handler
     */
    public function startConsumer(string $endpoint, callable $handler): Consumer;
}
