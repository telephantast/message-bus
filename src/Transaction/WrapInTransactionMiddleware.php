<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transaction;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class WrapInTransactionMiddleware implements Middleware
{
    public function __construct(
        private TransactionProvider $transactionProvider,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(InTransaction::class)) {
            return $pipeline->continue();
        }

        $messageContext->setAttribute(new InTransaction());

        return $this->transactionProvider->wrapInTransaction(static fn(): mixed => $pipeline->continue());
    }
}
