<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres\Internal;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresResult;
use Amp\Postgres\PostgresStatement;
use Amp\Postgres\PostgresTransaction;
use Thesis\MessageBus\Persistence\TransactionScope as TransactionScopeInterface;
use Thesis\Sync\Once;

/**
 * @internal
 *
 * @implements TransactionScopeInterface<PostgresLink>
 */
final class PostgresTransactionScope implements TransactionScopeInterface, PostgresLink
{
    public function __construct(
        private readonly PostgresLink $pg,
    ) {}

    /**
     * @var ?Once<PostgresTransaction>
     */
    private ?Once $state = null;

    private PostgresTransaction $begunTransaction {
        get {
            if ($this->state !== null) {
                return $this->state->await();
            }

            $pg = $this->pg;
            $this->state = new Once($pg->beginTransaction(...));

            $transaction = $this->state->await();

            foreach ($this->onCloseCallbacks as $onClose) {
                $transaction->onClose($onClose);
            }

            $this->onCloseCallbacks = [];

            return $transaction;
        }
    }

    /**
     * @var list<\Closure(): void>
     */
    private array $onCloseCallbacks = [];

    public object $transaction {
        get => $this;
    }

    public bool $hasBegun {
        get => $this->state !== null;
    }

    public function ensureBegun(): void
    {
        $this->begunTransaction;
    }

    public function commitIfBegun(): void
    {
        // todo onCloseCallbacks
        $this->state?->await()->commit();
    }

    public function rollbackIfActive(): void
    {
        if ($this->state === null) {
            // todo onCloseCallbacks

            return;
        }

        if (!$this->begunTransaction->isActive()) {
            return;
        }

        $this->begunTransaction->rollback();
    }

    public function query(string $sql): PostgresResult
    {
        return $this->begunTransaction->query($sql);
    }

    public function prepare(string $sql): PostgresStatement
    {
        return $this->begunTransaction->prepare($sql);
    }

    public function execute(string $sql, array $params = []): PostgresResult
    {
        return $this->begunTransaction->execute($sql, $params);
    }

    public function notify(string $channel, string $payload = ''): PostgresResult
    {
        return $this->begunTransaction->notify($channel, $payload);
    }

    public function beginTransaction(): PostgresTransaction
    {
        return $this->begunTransaction->beginTransaction();
    }

    public function quoteLiteral(string $data): string
    {
        return $this->pg->quoteLiteral($data);
    }

    public function quoteIdentifier(string $name): string
    {
        return $this->pg->quoteIdentifier($name);
    }

    public function escapeByteA(string $data): string
    {
        return $this->pg->escapeByteA($data);
    }

    public function getLastUsedAt(): int
    {
        return $this->pg->getLastUsedAt();
    }

    public function close(): void
    {
        // todo
    }

    public function isClosed(): bool
    {
        return $this->state?->await()->isClosed() ?? false;
    }

    public function onClose(\Closure $onClose): void
    {
        if ($this->state !== null) {
            $this->begunTransaction->onClose($onClose);

            return;
        }

        $this->onCloseCallbacks[] = $onClose;
    }
}
