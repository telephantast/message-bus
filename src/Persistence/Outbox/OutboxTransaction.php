<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Outbox;

use Thesis\MessageBus\Persistence\Transaction;
use Thesis\MessageBus\Persistence\TransactionClosed;

/**
 * @api
 * @template-covariant TTransaction of object
 * @extends Transaction<TTransaction>
 */
interface OutboxTransaction extends Transaction
{
    /**
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function insertOutbox(Outbox $outbox): void;
}
