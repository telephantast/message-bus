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
     * @param non-empty-string $consumer
     * @param callable(Envelope, Tx): list<Envelope> $handler
     */
    public function consume(string $consumer, callable $handler, Envelope $envelope): void;

    /**
     * @param non-empty-string $consumer
     * @param callable(Envelope, Tx): list<Envelope> $handler
     * @return callable(): void Stop
     */
    public function startConsumer(string $consumer, callable $handler): callable;
}
