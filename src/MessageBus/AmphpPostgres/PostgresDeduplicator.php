<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres;

use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\Consumption\Deduplicator;

/**
 * PostgreSQL deduplicator for one endpoint.
 *
 * Pass an endpoint-specific schema/table pair. Reusing the same schema/table
 * pair for multiple endpoints can incorrectly deduplicate published events,
 * because the same event message id is delivered to multiple endpoints by
 * design.
 *
 * @api
 *
 * @implements Deduplicator<PostgresLink>
 */
final class PostgresDeduplicator implements Deduplicator
{
    /**
     * @param non-empty-string $table
     * @param non-empty-string $schema
     */
    public function __construct(
        private readonly PostgresLink $postgres,
        private readonly string $table,
        private readonly string $schema = 'public',
    ) {}

    /**
     * @phpstan-ignore property.uninitialized
     */
    private string $escapedSchema {
        get => $this->escapedSchema ??= $this->postgres->quoteIdentifier($this->schema);
    }

    /**
     * @phpstan-ignore property.uninitialized
     */
    private string $escapedTable {
        get => $this->escapedTable ??= $this->postgres->quoteIdentifier($this->table);
    }

    public function setup(): void
    {
        $this->postgres->query(
            <<<SQL
                create schema if not exists {$this->escapedSchema}
                SQL,
        );
        $this->postgres->query(
            <<<SQL
                create table if not exists {$this->escapedSchema}.{$this->escapedTable} (
                    message_id text not null,
                    handled_at timestamptz not null default now(),
                    primary key (message_id)
                )
                SQL,
        );
    }

    public function isHandled(string $messageId): bool
    {
        $result = $this->postgres->execute(
            <<<SQL
                select 1
                from {$this->escapedSchema}.{$this->escapedTable}
                where message_id = :message_id
                SQL,
            [
                'message_id' => $messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }

    public function markHandled(string $messageId): bool
    {
        return $this->markHandledInTransaction($this->postgres, $messageId);
    }

    public function markHandledInTransaction(object $transaction, string $messageId): bool
    {
        $result = $transaction->execute(
            <<<SQL
                insert into {$this->escapedSchema}.{$this->escapedTable} (message_id)
                values (:message_id)
                on conflict do nothing
                returning 1
                SQL,
            [
                'message_id' => $messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }
}
