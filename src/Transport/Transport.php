<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface Transport
{
    /**
     * @param non-empty-string $endpoint
     * @param list<class-string<Message>> $localMessages
     */
    public function setup(string $endpoint, array $localMessages): void;

    /**
     * @param non-empty-list<Envelope> $envelopes
     * @throws FailedToPublishMessages
     */
    public function publish(array $envelopes): void;

    /**
     * @param non-empty-string $endpoint
     * @param \Closure(Envelope): void $handler
     * @return \Closure(): void the cancel function
     */
    public function consume(string $endpoint, \Closure $handler): \Closure;
}
