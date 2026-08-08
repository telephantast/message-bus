<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres;

use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\Consumption\Deduplicator;
use Thesis\MessageBus\Consumption\ProcessingId;

/**
 * @api
 *
 * @implements Deduplicator<PostgresLink>
 */
final class PostgresDeduplicator implements Deduplicator
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        private readonly PostgresLink $pg,
        private readonly string $table = 'processed_message',
        private readonly string $schema = 'thesis_message_bus',
    ) {}

    /**
     * @phpstan-ignore property.uninitialized
     */
    private string $escapedSchema {
        get => $this->escapedSchema ??= $this->pg->quoteIdentifier($this->schema);
    }

    /**
     * @phpstan-ignore property.uninitialized
     */
    private string $escapedTable {
        get => $this->escapedTable ??= $this->pg->quoteIdentifier($this->table);
    }

    public function setup(string $endpoint): void
    {
        $this->pg->query(
            <<<SQL
                create schema if not exists {$this->escapedSchema}
                SQL,
        );
        $this->pg->query(
            <<<SQL
                create table if not exists {$this->escapedSchema}.{$this->escapedTable} (
                    endpoint text not null,
                    message_id text not null,
                    handled_at timestamptz not null default now(),
                    primary key (message_id, endpoint)
                )
                SQL,
        );
    }

    public function isHandled(ProcessingId $id): bool
    {
        $result = $this->pg->execute(
            <<<SQL
                select 1
                from {$this->escapedSchema}.{$this->escapedTable}
                where endpoint = :endpoint and message_id = :message_id
                SQL,
            [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }

    public function markHandled(ProcessingId $id): bool
    {
        return $this->markHandledInTransaction($this->pg, $id);
    }

    public function markHandledInTransaction(object $transaction, ProcessingId $id): bool
    {
        $result = $transaction->execute(
            <<<SQL
                insert into {$this->escapedSchema}.{$this->escapedTable} (endpoint, message_id)
                values (:endpoint, :message_id)
                on conflict do nothing
                returning 1
                SQL,
            [
                'endpoint' => $id->endpoint,
                'message_id' => $id->messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }
}
