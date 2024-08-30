<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Transaction;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final class WrapInTransactionMiddleware implements Middleware
{
    public function __construct(
        private readonly TransactionProvider $transactionProvider,
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
