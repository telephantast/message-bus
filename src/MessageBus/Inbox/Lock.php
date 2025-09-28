<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Inbox;

use Amp\Cancellation;
use Thesis\MessageBus\ConsumptionId;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Lock
{
    /**
     * @param Tx $transaction
     */
    public function acquire(object $transaction, ConsumptionId $id, Cancellation $cancellation): AcquireResult;
}
