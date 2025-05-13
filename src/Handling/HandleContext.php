<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\Envelope;
use Thesis\Message\Message;

/**
 * @api
 * @template-covariant TTransaction of object = object
 */
final class HandleContext
{
    /**
     * @param non-empty-string $endpoint
     * @param TTransaction $transaction
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly object $transaction,
    ) {}

    /**
     * @var list<Envelope>
     */
    public private(set) array $dispatchedEnvelopes = [];

    public function dispatch(Message|Envelope $message): void
    {
        $this->dispatchedEnvelopes[] = Envelope::wrap($message);
    }
}
