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
    public function setup(): void;

    /**
     * @return Transaction<TTransaction>
     */
    public function beginTransaction(Endpoint $endpoint): Transaction;

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
