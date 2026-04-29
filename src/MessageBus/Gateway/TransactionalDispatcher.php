<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

use Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface TransactionalDispatcher
{
    /**
     * @param Tx $transaction
     * @param non-empty-list<Envelope> $messages
     */
    public function transactionalDispatch(object $transaction, array $messages): void;
}
