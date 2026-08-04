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
    private ?Once $pgTransaction = null;

    /**
     * @var list<\Closure(): void>
     */
    private array $onCloseCallbacks = [];

    public object $transaction {
        get => $this;
    }

    public bool $hasBegun {
        get => $this->pgTransaction !== null;
    }

    public function begin(): void
    {
        $this->begunTransaction();
    }

    public function commitIfBegun(): void
    {
        $this->pgTransaction?->await()->commit();
    }

    public function rollbackIfActive(): void
    {
        if ($this->pgTransaction === null) {
            return;
        }

        if (!$this->begunTransaction()->isActive()) {
            return;
        }

        $this->begunTransaction()->rollback();
    }

    public function query(string $sql): PostgresResult
    {
        return $this->begunTransaction()->query($sql);
    }

    public function prepare(string $sql): PostgresStatement
    {
        return $this->begunTransaction()->prepare($sql);
    }

    public function execute(string $sql, array $params = []): PostgresResult
    {
        return $this->begunTransaction()->execute($sql, $params);
    }

    public function notify(string $channel, string $payload = ''): PostgresResult
    {
        return $this->begunTransaction()->notify($channel, $payload);
    }

    public function beginTransaction(): PostgresTransaction
    {
        return $this->begunTransaction()->beginTransaction();
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
        $this->rollbackIfActive();
    }

    public function isClosed(): bool
    {
        return $this->pgTransaction?->await()->isClosed() ?? false;
    }

    public function onClose(\Closure $onClose): void
    {
        if ($this->pgTransaction !== null) {
            $this->begunTransaction()->onClose($onClose);

            return;
        }

        $this->onCloseCallbacks[] = $onClose;
    }

    private function begunTransaction(): PostgresTransaction
    {
        if ($this->pgTransaction !== null) {
            return $this->pgTransaction->await();
        }

        $pg = $this->pg;
        $this->pgTransaction = new Once($pg->beginTransaction(...));

        $transaction = $this->pgTransaction->await();

        foreach ($this->onCloseCallbacks as $onClose) {
            $transaction->onClose($onClose);
        }

        $this->onCloseCallbacks = [];

        return $transaction;
    }
}
