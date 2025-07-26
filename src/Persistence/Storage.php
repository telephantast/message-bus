<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Endpoint;

/**
 * @api
 * @template-covariant TTransaction of object
 */
interface Storage
{
    /**
     * @var non-empty-list<class-string<TTransaction>>
     * @phpstan-ignore generics.variance
     */
    public array $transactionClasses { get; }

    public function setup(): void;

    /**
     * @return LazyTransaction<TTransaction>
     */
    public function beginTransaction(Endpoint $endpoint): LazyTransaction;

    /**
     * @param non-empty-list<non-empty-string> $incomingMessageIds
     * @return list<Outbox>
     */
    public function findOutboxes(Endpoint $endpoint, array $incomingMessageIds): array;

    /**
     * @param non-empty-list<non-empty-string> $incomingMessageIds
     */
    public function markOutboxesDispatched(Endpoint $endpoint, array $incomingMessageIds): void;
}
