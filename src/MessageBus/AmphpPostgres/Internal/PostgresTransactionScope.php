<?php

declare(strict_types=1);

namespace Thesis\MessageBus\AmphpPostgres\Internal;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresResult;
use Amp\Postgres\PostgresStatement;
use Amp\Postgres\PostgresTransaction;
use Revolt\EventLoop;
use Thesis\MessageBus\Persistence\TransactionScope;
use Thesis\Sync\Once;

/**
 * @internal
 *
 * @implements TransactionScope<PostgresLink>
 */
final class PostgresTransactionScope implements TransactionScope, PostgresLink
{
    public function __construct(
        private readonly PostgresConnection $postgres,
    ) {}

    public bool $hasBegun { get => $this->state !== null; }

    /**
     * @var ?Once<PostgresTransaction>
     */
    private ?Once $state = null;

    public object $transaction { get => $this; }

    public function begin(): void
    {
        $this->doBegin();
    }

    public function commit(): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->state?->await()->commit();
        } finally {
            $this->doClose();
        }
    }

    public function rollback(): void
    {
        if ($this->closed) {
            return;
        }

        try {
            $this->state?->await()->rollback();
        } finally {
            $this->doClose();
        }
    }

    private function doBegin(): PostgresLink
    {
        $this->ensureNotClosed();

        $this->state ??= new Once($this->postgres->beginTransaction(...));

        return $this->state->await();
    }

    public function query(string $sql): PostgresResult
    {
        return $this->doBegin()->query($sql);
    }

    public function prepare(string $sql): PostgresStatement
    {
        return $this->doBegin()->prepare($sql);
    }

    public function execute(string $sql, array $params = []): PostgresResult
    {
        return $this->doBegin()->execute($sql, $params);
    }

    public function notify(string $channel, string $payload = ''): PostgresResult
    {
        return $this->doBegin()->notify($channel, $payload);
    }

    public function beginTransaction(): PostgresTransaction
    {
        return $this->doBegin()->beginTransaction();
    }

    public function quoteLiteral(string $data): string
    {
        return $this->postgres->quoteLiteral($data);
    }

    public function quoteIdentifier(string $name): string
    {
        return $this->postgres->quoteIdentifier($name);
    }

    public function escapeByteA(string $data): string
    {
        return $this->postgres->escapeByteA($data);
    }

    public function getLastUsedAt(): int
    {
        return $this->postgres->getLastUsedAt();
    }

    private bool $closed = false;

    /**
     * @var list<\Closure(): void>
     */
    private array $closeCallbacks = [];

    public function close(): void
    {
        $this->rollback();
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function onClose(\Closure $onClose): void
    {
        if ($this->closed) {
            EventLoop::queue($onClose);

            return;
        }

        $this->closeCallbacks[] = $onClose;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new \LogicException('This transaction scope has already been closed.');
        }
    }

    private function doClose(): void
    {
        $this->closed = true;

        foreach ($this->closeCallbacks as $callback) {
            EventLoop::queue($callback);
        }

        $this->closeCallbacks = [];
    }
}
