<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres\Internal;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresTransaction as AmpPostgresTransaction;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @internal
 *
 * @implements Transaction<PostgresLink>
 */
final class PostgresTransaction implements Transaction
{
    public PostgresLink $handle {
        get => $this->transaction;
    }

    public function __construct(
        private readonly AmpPostgresTransaction $transaction,
    ) {}

    public function commit(): void
    {
        $this->transaction->commit();
    }

    public function rollback(): void
    {
        $this->transaction->rollback();
    }
}
