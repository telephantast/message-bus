<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Async\Queue;
use Telephantast\MessageBus\Async\TransportPublish;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final class OutboxConsumerMiddleware implements Middleware
{
    public function __construct(
        private readonly OutboxStorage $outboxStorage,
        private readonly TransactionProvider $transactionProvider,
        private readonly TransportPublish $transportPublish,
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

            try {
                $this->transactionProvider->wrapInTransaction(function () use ($pipeline, $queue, $messageId, $outbox): void {
                    $pipeline->continue();
                    $this->outboxStorage->create($queue, $messageId, $outbox);
                });
            } catch (OutboxAlreadyExists) {
                $outbox = $this->outboxStorage->get($queue, $messageId);
            }
        }

        if ($outbox !== null && $outbox->envelopes !== []) {
            $this->transportPublish->publish($outbox->envelopes);
            $this->outboxStorage->empty($queue, $messageId);
        }

        return null;
    }
}
