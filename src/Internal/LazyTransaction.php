<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @template TTransaction of object
 */
final class LazyTransaction
{
    /**
     * @param Storage<TTransaction> $storage
     * @param non-empty-string $incomingMessageId
     */
    public function __construct(
        private readonly Storage $storage,
        private readonly Endpoint $endpoint,
        private readonly string $incomingMessageId,
    ) {}

    /**
     * @var ?Transaction<TTransaction>
     */
    private ?Transaction $transaction = null;

    /**
     * @return Transaction<TTransaction>
     */
    public function begin(): Transaction
    {
        return $this->transaction ??= $this->storage->beginTransaction($this->endpoint, $this->incomingMessageId);
    }

    public function commitIfBegun(): void
    {
        $this->transaction?->commit();
    }

    public function rollbackIfBegun(): void
    {
        $this->transaction?->rollback();
    }

    /**
     * @return TTransaction
     */
    public function __invoke(): object
    {
        return $this->begin()->wrappedTransaction;
    }
}
