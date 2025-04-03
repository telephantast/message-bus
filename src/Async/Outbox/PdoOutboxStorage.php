<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class PdoOutboxStorage implements OutboxStorage
{
    /**
     * @param literal-string $table
     */
    public function __construct(
        private \PDO $connection,
        private string $table = 'thesis_outbox',
    ) {}

    public function setup(): void
    {
        $this->connection->exec(
            <<<SQL
                create table if not exists {$this->table}
                (
                    message_id text not null,
                    queue      text not null,
                    envelopes  text not null,
                    primary key (message_id, queue)
                )
                SQL,
        );
    }

    public function get(string $messageId, string $queue): ?Outbox
    {
        $statement = $this->connection->prepare(
            <<<SQL
                select envelopes
                from {$this->table}
                where message_id = ? and queue = ?
                SQL,
        );
        \assert($statement !== false);
        $statement->execute([$messageId, $queue]);

        /** @psalm-suppress MixedAssignment */
        $serializedEnvelopes = $statement->fetchColumn();

        if (\is_string($serializedEnvelopes)) {
            /** @var list<Envelope<null, Message<null>>> */
            $envelopes = unserialize($serializedEnvelopes);

            return new Outbox(
                messageId: $messageId,
                queue: $queue,
                envelopes: $envelopes,
            );
        }

        return null;
    }

    public function insert(Outbox $outbox): void
    {
        $statement = $this->connection->prepare(
            <<<SQL
                insert into {$this->table} (message_id, queue, envelopes)
                values (?, ?, ?)
                on conflict (message_id, queue) do nothing
                SQL,
        );
        \assert($statement !== false);

        $statement->execute([$outbox->messageId, $outbox->queue, serialize($outbox->envelopes)]);

        if ($statement->rowCount() === 0) {
            throw new OutboxAlreadyExists();
        }
    }

    public function update(Outbox $outbox): void
    {
        $statement = $this->connection->prepare(
            <<<SQL
                update {$this->table}
                set envelopes = ?
                where message_id = ? and queue = ?
                SQL,
        );
        \assert($statement !== false);

        $statement->execute([
            serialize($outbox->envelopes),
            $outbox->messageId,
            $outbox->queue,
        ]);
    }
}
