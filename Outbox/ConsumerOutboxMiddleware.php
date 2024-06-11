<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Async\Publish;
use Telephantast\MessageBus\Async\Queue;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final readonly class ConsumerOutboxMiddleware implements Middleware
{
    public function __construct(
        private OutboxStorage $outboxStorage,
        private TransactionProvider $transactionProvider,
        private Publish $publish,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(Outbox::class)) {
            return $pipeline->continue();
        }

        $queue = $messageContext->getAttribute(Queue::class)?->queue ?? throw new \LogicException();
        $messageId = $messageContext->getMessageId();

        $outbox = $this->outboxStorage->get($queue, $messageId);

        if ($outbox === null) {
            $outbox = new Outbox();
            $messageContext->setAttribute($outbox);
            $this->transactionProvider->wrapInTransaction(function () use ($pipeline, $queue, $messageId, $outbox): void {
                $pipeline->continue();
                $this->outboxStorage->save($queue, $messageId, $outbox);
            });
        }

        if ($outbox->envelopes !== []) {
            $this->publish->publish($outbox->envelopes);
            $this->outboxStorage->save($queue, $messageId, new Outbox());
        }

        return null;
    }
}
