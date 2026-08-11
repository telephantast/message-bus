<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmpPostgres;

use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\Consumption\Deduplicator;

/**
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
        private readonly string $schema = 'thesis_message_bus',
        private readonly string $table = 'processed_message',
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
                    endpoint text not null,
                    message_id text not null,
                    handled_at timestamptz not null default now(),
                    primary key (message_id, endpoint)
                )
                SQL,
        );
    }

    public function isHandled(string $endpoint, string $messageId): bool
    {
        $result = $this->postgres->execute(
            <<<SQL
                select 1
                from {$this->escapedSchema}.{$this->escapedTable}
                where endpoint = :endpoint and message_id = :message_id
                SQL,
            [
                'endpoint' => $endpoint,
                'message_id' => $messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }

    public function markHandled(string $endpoint, string $messageId): bool
    {
        return $this->markHandledInTransaction($this->postgres, $endpoint, $messageId);
    }

    public function markHandledInTransaction(object $transaction, string $endpoint, string $messageId): bool
    {
        $result = $transaction->execute(
            <<<SQL
                insert into {$this->escapedSchema}.{$this->escapedTable} (endpoint, message_id)
                values (:endpoint, :message_id)
                on conflict do nothing
                returning 1
                SQL,
            [
                'endpoint' => $endpoint,
                'message_id' => $messageId,
            ],
        );

        return $result->fetchRow() !== null;
    }

    public function purgeHandledBefore(\DateTimeImmutable $handledBefore): int
    {
        return $this
            ->postgres
            ->execute(
                <<<SQL
                    delete from {$this->escapedSchema}.{$this->escapedTable}
                    where handled_at < ?
                    SQL,
                [
                    $handledBefore->format('Y-m-d H:i:s.uP'),
                ],
            )
            ->getRowCount() ?? 0;
    }
}
