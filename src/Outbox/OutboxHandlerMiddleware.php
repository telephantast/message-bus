<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\MessageBus\Async\TransportPublish;
use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;
use Thesis\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final readonly class OutboxHandlerMiddleware implements Middleware
{
    public function __construct(
        private OutboxStorage $outboxStorage,
        private TransactionProvider $transactionProvider,
        private TransportPublish $transportPublish,
        private LoggerInterface $logger = new NullLogger(),
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

            if (!$outbox->isEmpty()) {
                $this->outboxStorage->create(null, $messageId, $outbox);
            }

            return $result;
        });

        if ($outbox->isEmpty()) {
            return $result;
        }

        try {
            $this->transportPublish->publish($outbox->getEnvelopes());
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to publish outboxed messages.', [
                'exception' => $exception,
                'message_class' => $messageContext->getMessageClass(),
                'handler_id' => $pipeline->id(),
                'envelope' => $messageContext->getEnvelope(),
            ]);

            return $result;
        }

        try {
            $this->outboxStorage->empty(null, $messageId);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to empty outbox.', [
                'exception' => $exception,
                'message_id' => $messageId,
            ]);
        }

        return $result;
    }
}
