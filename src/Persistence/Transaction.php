<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 */
interface Transaction
{
    /**
     * @throws OutboxAlreadyExists
     * @throws TransactionClosed
     */
    public function commit(Outbox $outbox): void;

    /**
     * @throws TransactionClosed
     */
    public function rollback(): void;
}
