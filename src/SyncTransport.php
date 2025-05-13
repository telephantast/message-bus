<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

interface SyncTransport
{
    /**
     * @template TResult
     * @param non-empty-string $endpoint
     * @param Envelope<TResult, Message<TResult>> $envelope
     * @return TResult
     */
    public function request(string $endpoint, Envelope $envelope): mixed;

    /**
     * @param non-empty-string $endpoint
     * @param \Closure(Envelope<mixed>): mixed $handler
     * @return \Closure(): void the cancel function
     */
    public function consume(string $endpoint, \Closure $handler): \Closure;
}
