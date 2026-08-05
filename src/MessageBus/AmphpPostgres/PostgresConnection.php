<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres;

use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\AmphpPostgres\Internal\PostgresTransaction;
use Thesis\MessageBus\Persistence\Connection;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 *
 * @implements Connection<PostgresLink>
 */
final readonly class PostgresConnection implements Connection
{
    public function __construct(
        private PostgresLink $postgres,
    ) {}

    public function begin(): Transaction
    {
        return new PostgresTransaction($this->postgres->beginTransaction());
    }
}
