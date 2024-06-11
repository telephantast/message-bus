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
final readonly class OutgoingEnvelope
{
    /**
     * @param non-empty-string $exchange
     * @param Envelope<TResult, TMessage> $envelope
     */
    public function __construct(
        public string $exchange,
        public Envelope $envelope,
    ) {}
}
