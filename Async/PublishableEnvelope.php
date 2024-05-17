<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Envelope;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 */
final readonly class PublishableEnvelope
{
    /**
     * @param non-empty-string $exchange
     * @param Envelope<TResult, TMessage> $envelope
     * @param ?callable(Envelope): void $onSuccess
     * @param ?callable(Envelope, \Throwable): void $onFailure
     */
    public function __construct(
        public string $exchange,
        public Envelope $envelope,
        private mixed $onSuccess = null,
        private mixed $onFailure = null,
    ) {}

    public function onSuccess(): void
    {
        if ($this->onSuccess !== null) {
            ($this->onSuccess)($this->envelope);
        }
    }

    public function onFailure(\Throwable $reason): void
    {
        if ($this->onFailure !== null) {
            ($this->onFailure)($this->envelope, $reason);
        }
    }
}
