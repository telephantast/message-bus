<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface TransactionalDispatcher extends Dispatcher
{
    /**
     * Atomically records all envelopes.
     *
     * Implementations must manage any database transaction themselves. Ideally, this
     * should be done with one database query on a separate connection.
     *
     * @param non-empty-list<OutboundEnvelope> $envelopes
     */
    public function dispatch(array $envelopes): void;

    /**
     * Atomically records all envelopes in the given transaction.
     *
     * Implementations must participate in the supplied transaction context and must
     * not commit or roll it back.
     *
     * @param Tx $transaction
     * @param non-empty-list<OutboundEnvelope> $envelopes
     */
    public function dispatchInTransaction(object $transaction, array $envelopes): void;
}
