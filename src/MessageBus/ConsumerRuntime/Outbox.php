<?php

declare(strict_types=1);

namespace Thesis\MessageBus\ConsumerRuntime;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Outbox
{
    public function find(ConsumptionId $id): ?OutboxRecord;

    /**
     * @param Tx $transaction
     */
    public function store(object $transaction, ConsumptionId $id, OutboxRecord $record): void;

    public function markDispatched(ConsumptionId $id): void;
}
