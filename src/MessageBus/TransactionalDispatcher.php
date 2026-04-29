<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface TransactionalDispatcher extends Dispatcher
{
    /**
     * @param Tx $transaction
     * @param non-empty-list<OutgoingEnvelope> $envelopes
     */
    public function transactionalDispatch(object $transaction, array $envelopes): void;
}
