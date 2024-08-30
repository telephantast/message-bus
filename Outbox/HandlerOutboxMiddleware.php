<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Async\TransportPublish;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final class HandlerOutboxMiddleware implements Middleware
{
    public function __construct(
        private readonly OutboxStorage $outboxStorage,
        private readonly TransactionProvider $transactionProvider,
        private readonly TransportPublish $publish,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(Outbox::class)) {
            return $pipeline->continue();
        }

        $messageId = $messageContext->getMessageId();
        $outbox = new Outbox();
        $messageContext->setAttribute($outbox);

        $result = $this->transactionProvider->wrapInTransaction(function () use ($pipeline, $messageId, $outbox): mixed {
            $result = $pipeline->continue();

            if ($outbox->envelopes !== []) {
                $this->outboxStorage->create(null, $messageId, $outbox);
            }

            return $result;
        });

        if ($outbox->envelopes !== []) {
            $this->publish->publish($outbox->envelopes);
            $this->outboxStorage->empty(null, $messageId);
        }

        return $result;
    }
}
