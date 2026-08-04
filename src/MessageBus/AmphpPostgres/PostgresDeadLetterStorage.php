<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres;

use Amp\Postgres\PostgresLink;
use Thesis\MessageBus\Recoverability\DeadLetterStorage;
use Thesis\MessageBus\Recoverability\FailureContext;
use Thesis\MessageBus\Transport\InboundEnvelope;
use const Thesis\MessageBus\MESSAGE_ID;

/**
 * @api
 */
final class PostgresDeadLetterStorage implements DeadLetterStorage
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        private readonly PostgresLink $pg,
        private readonly string $table = 'message_bus_dead_letter',
    ) {}

    /**
     * @phpstan-ignore property.uninitialized
     */
    private string $escapedTable {
        get => $this->escapedTable ??= $this->pg->quoteIdentifier($this->table);
    }

    public function setup(): void
    {
        $this->pg->execute(
            <<<SQL
                create table if not exists {$this->escapedTable} (
                    id bigserial primary key,
                    hash text not null unique,
                    endpoint text not null,
                    message_id text,
                    payload text not null,
                    headers jsonb not null,
                    error_class text not null,
                    error_message text not null,
                    error_file text not null,
                    error_line integer not null,
                    error_code integer not null,
                    error_trace text not null,
                    retry_count integer not null,
                    failed_at timestamptz not null
                )
                SQL,
        );
    }

    public function store(InboundEnvelope $envelope, FailureContext $context): void
    {
        $this->pg->execute(
            <<<SQL
                insert into {$this->escapedTable} (
                    hash,
                    endpoint,
                    message_id,
                    payload,
                    headers,
                    error_class,
                    error_message,
                    error_file,
                    error_line,
                    error_code,
                    error_trace,
                    retry_count,
                    failed_at
                )
                values (
                    :hash,
                    :endpoint,
                    :message_id,
                    :payload,
                    :headers,
                    :error_class,
                    :error_message,
                    :error_file,
                    :error_line,
                    :error_code,
                    :error_trace,
                    :retry_count,
                    :failed_at
                )
                on conflict (hash) do nothing
                SQL,
            [
                'hash' => self::hash($envelope, $context),
                'endpoint' => $context->endpoint,
                'message_id' => $envelope->headers->find(MESSAGE_ID),
                'payload' => $envelope->payload,
                'headers' => self::jsonEncode($envelope->headers->encode()),
                'error_class' => $context->error::class,
                'error_message' => $context->error->getMessage(),
                'error_file' => $context->error->getFile(),
                'error_line' => $context->error->getLine(),
                'error_code' => $context->error->getCode(),
                'error_trace' => $context->error->getTraceAsString(),
                'retry_count' => $context->delayedRetryCount,
                'failed_at' => $context->startedAt->format(\DateTimeInterface::ATOM),
            ],
        );
    }

    private static function hash(InboundEnvelope $envelope, FailureContext $context): string
    {
        return hash('sha256', self::jsonEncode([
            $context->endpoint,
            $envelope->payload,
            $envelope->headers->encode(),
            $context->error::class,
            $context->error->getMessage(),
            $context->error->getFile(),
            $context->error->getLine(),
            $context->error->getCode(),
            $context->error->getTraceAsString(),
            $context->delayedRetryCount,
            $context->startedAt->format(\DateTimeInterface::ATOM),
        ]));
    }

    private static function jsonEncode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
