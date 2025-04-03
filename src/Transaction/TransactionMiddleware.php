<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transaction;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class TransactionMiddleware implements Middleware
{
    public function __construct(
        private TransactionProvider $transactionProvider,
    ) {}

    public function handle(Context $context, Pipeline $pipeline): mixed
    {
        return $this->transactionProvider->wrapInTransaction(static fn(): mixed => $pipeline->continue());
    }
}
