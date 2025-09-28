<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface ReliableDispatcher
{
    /**
     * @param non-empty-list<Envelope> $envelopes
     * @param Tx $transaction
     */
    public function record(ConsumptionId $id, array $envelopes, object $transaction, Cancellation $cancellation): void;

    public function dispatchRecorded(ConsumptionId $id, Cancellation $cancellation): void;
}
