<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

use Thesis\MessageBus\ConsumptionId;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Gateway\Outbox\Record;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Outbox
{
    public function find(ConsumptionId $id): ?Record;

    /**
     * @param Tx $transaction
     * @param non-empty-list<Envelope> $envelopes
     */
    public function store(ConsumptionId $id, object $transaction, array $envelopes): void;

    public function markDispatched(ConsumptionId $id): void;
}
