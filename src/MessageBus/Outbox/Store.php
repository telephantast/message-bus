<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Amp\Cancellation;
use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Store
{
    /**
     * @return list<Envelope>
     */
    public function find(ConsumptionId $id, Cancellation $cancellation): array;

    /**
     * @param Tx $transaction
     * @param non-empty-list<Envelope> $envelopes
     */
    public function record(object $transaction, ConsumptionId $id, array $envelopes, Cancellation $cancellation): void;

    public function markDispatched(ConsumptionId $id, Cancellation $cancellation): void;
}
