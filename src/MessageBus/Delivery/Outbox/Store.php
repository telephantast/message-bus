<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Delivery\Outbox;

use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Store
{
    public function find(ConsumptionId $id): ?Record;

    /**
     * @param Tx $transaction
     * @param non-empty-list<Envelope> $envelopes
     */
    public function store(ConsumptionId $id, object $transaction, array $envelopes): void;

    public function markDispatched(ConsumptionId $id): void;
}
