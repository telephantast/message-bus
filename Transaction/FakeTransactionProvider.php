<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Transaction;

/**
 * @api
 */
final readonly class FakeTransactionProvider implements TransactionProvider
{
    public function wrapInTransaction(callable $operation): mixed
    {
        return $operation();
    }
}
