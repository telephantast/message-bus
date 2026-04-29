<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface Gateway
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope, Tx): list<Envelope> $handler
     */
    public function consume(string $endpoint, callable $handler, Envelope $envelope): void;

    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope, Tx): list<Envelope> $handler
     */
    public function startConsumer(string $endpoint, callable $handler): Consumer;
}
