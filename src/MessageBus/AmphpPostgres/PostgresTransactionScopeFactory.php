<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\AmphpPostgres\Internal\PostgresTransactionScope;
use Thesis\MessageBus\Persistence\TransactionScope;
use Thesis\MessageBus\Persistence\TransactionScopeFactory;

/**
 * @api
 *
 * @implements TransactionScopeFactory<PostgresLink>
 */
final readonly class PostgresTransactionScopeFactory implements TransactionScopeFactory
{
    public function __construct(
        private PostgresConnection $postgres,
    ) {}

    public function create(): TransactionScope
    {
        return new PostgresTransactionScope($this->postgres);
    }
}
