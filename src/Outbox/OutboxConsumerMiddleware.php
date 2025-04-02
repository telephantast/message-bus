<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\MessageBus\Async\Queue;
use Thesis\MessageBus\Async\TransportPublish;
use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;
use Thesis\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final readonly class OutboxConsumerMiddleware implements Middleware
{
    public function __construct(
        private OutboxStorage $outboxStorage,
        private TransactionProvider $transactionProvider,
        private TransportPublish $transportPublish,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasAttribute(Outbox::class)) {
            return $pipeline->continue();
        }

        $queue = $messageContext->getAttribute(Queue::class)->queue
            ?? throw new \LogicException(\sprintf('`%s` must be used in a consumer only', self::class));
        $messageId = $messageContext->getMessageId();

        $outbox = $this->outboxStorage->get($queue, $messageId);

        if ($outbox === null) {
            $outbox = new Outbox();
            $messageContext->setAttribute($outbox);

            try {
                $this->transactionProvider->wrapInTransaction(
                    function () use ($pipeline, $queue, $messageId, $outbox): void {
                        $pipeline->continue();
                        $this->outboxStorage->create($queue, $messageId, $outbox);
                    },
                );
            } catch (OutboxAlreadyExists) {
                /** @phpstan-ignore return.type */
                return null;
            }
        }

        if (!$outbox->isEmpty()) {
            $this->transportPublish->publish($outbox->getEnvelopes());
            $this->outboxStorage->empty($queue, $messageId);
        }

        /** @phpstan-ignore return.type */
        return null;
    }
}
