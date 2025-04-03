<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Thesis\Message\Message;
use Thesis\MessageBus\Async\InConsumer;
use Thesis\MessageBus\Async\TransportPublish;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;
use Thesis\MessageBus\Transaction\TransactionProvider;

/**
 * @api
 */
final readonly class OutboxMiddleware implements Middleware
{
    public const string CONTROLLER_QUEUE = '';

    public function __construct(
        private OutboxStorage $outboxStorage,
        private TransactionProvider $transactionProvider,
        private TransportPublish $transportPublish,
        private LoggerInterface $logger = new NullLogger(),
        private mixed $logLevel = LogLevel::WARNING,
    ) {}

    public function handle(Context $context, Pipeline $pipeline): mixed
    {
        if ($context->hasAttribute(OutboxCollector::class)) {
            return $pipeline->continue();
        }

        $inConsumer = $context->getAttribute(InConsumer::class);

        if ($inConsumer === null) {
            return $this->controller($context, $pipeline);
        }

        $this->consumer($context, $pipeline, $inConsumer->queue);

        /** @phpstan-ignore return.type */
        return null;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Context<TResult, TMessage> $context
     * @param Pipeline<TResult, TMessage> $pipeline
     * @return TResult
     */
    private function controller(Context $context, Pipeline $pipeline): mixed
    {
        $messageId = $context->envelope->messageId;

        $collector = new OutboxCollector();
        $context->addAttribute($collector);

        $result = $this->transactionProvider->wrapInTransaction(
            function () use ($messageId, $pipeline, $collector): mixed {
                $result = $pipeline->continue();

                if (!$collector->isEmpty()) {
                    $this->outboxStorage->insert(
                        new Outbox(
                            messageId: $messageId,
                            queue: self::CONTROLLER_QUEUE,
                            envelopes: $collector->getEnvelopes(),
                        ),
                    );
                }

                return $result;
            },
        );

        if ($collector->isEmpty()) {
            return $result;
        }

        try {
            $this->transportPublish->publish($collector->getEnvelopes());
        } catch (\Throwable $exception) {
            $this->logger->log($this->logLevel, 'Failed to publish outboxed messages', [
                'exception' => $exception,
                'message_id' => $messageId,
            ]);

            return $result;
        }

        try {
            $this->outboxStorage->update(
                new Outbox(
                    messageId: $messageId,
                    queue: self::CONTROLLER_QUEUE,
                ),
            );
        } catch (\Throwable $exception) {
            $this->logger->log($this->logLevel, 'Failed to update outbox', [
                'exception' => $exception,
                'message_id' => $messageId,
            ]);
        }

        return $result;
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Context<TResult, TMessage> $context
     * @param Pipeline<TResult, TMessage> $pipeline
     * @param non-empty-string $queue
     */
    private function consumer(Context $context, Pipeline $pipeline, string $queue): void
    {
        $messageId = $context->envelope->messageId;

        $outbox = $this->outboxStorage->get($messageId, $queue);

        if ($outbox === null) {
            $collector = new OutboxCollector();
            $context->addAttribute($collector);

            $this->transactionProvider->wrapInTransaction(
                function () use ($pipeline, $collector, $messageId, $queue): void {
                    $pipeline->continue();
                    $outbox = new Outbox(
                        messageId: $messageId,
                        queue: $queue,
                        envelopes: $collector->getEnvelopes(),
                    );
                    $this->outboxStorage->insert($outbox);
                },
            );

            $envelopes = $collector->getEnvelopes();
        } else {
            $envelopes = $outbox->envelopes;
        }

        if ($envelopes === []) {
            return;
        }

        $this->transportPublish->publish($envelopes);
        $this->outboxStorage->update(new Outbox($messageId, $queue));
    }
}
