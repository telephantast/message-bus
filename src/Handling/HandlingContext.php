<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\Envelope;
use Thesis\Message\Message;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 * @template-covariant TTransaction of Transaction = Transaction
 */
final class HandlingContext
{
    /**
     * @param non-empty-string $endpoint
     * @param TTransaction $transaction
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly Transaction $transaction,
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
