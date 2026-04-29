<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\ConsumerRuntime\Consumer;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface ConsumerRuntime
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope, Tx): list<OutgoingEnvelope> $handler
     */
    public function consume(string $endpoint, Envelope $envelope, callable $handler): void;

    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope, Tx): list<OutgoingEnvelope> $handler
     */
    public function startConsumer(string $endpoint, callable $handler): Consumer;
}
