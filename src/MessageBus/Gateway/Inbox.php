<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

use Thesis\MessageBus\ConsumptionId;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Inbox
{
    /**
     * Marks the message as handled and returns whether it was already handled before.
     *
     * @param Tx $transaction
     */
    public function isHandled(object $transaction, ConsumptionId $consumptionId): bool;
}
