<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\MessageBus\CollectingPublisher;
use Thesis\MessageBus\CollectingSender;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\Context;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Pipeline;
use Thesis\MessageBus\Publisher;
use Thesis\MessageBus\Sender;

final readonly class OutboxMiddleware implements Middleware
{
    /**
     * @param OutboxStorage<*> $storage
     */
    public function __construct(
        private OutboxStorage $storage,
    ) {}

    public function handle(string $endpoint, Envelope $envelope, Context $context, Pipeline $pipeline): mixed
    {
        if ($context->has(Outboxed::class)) {
            return $pipeline->continue();
        }

        $outbox = $this->storage->findOutbox(
            endpoint: $endpoint,
            incomingMessageId: $envelope->messageId,
        );

        if ($outbox !== null) {
            $context->get(Sender::class)->send(...$outbox->commands);
            $context->get(Publisher::class)->publish(...$outbox->events);

            /** @phpstan-ignore return.type */
            return null;
        }

        $transaction = $this->storage->beginTransaction();

        try {
            $originalSender = $context->get(Sender::class);
            $originalPublisher = $context->get(Publisher::class);
            $sender = new CollectingSender();
            $publisher = new CollectingPublisher();
            $context = $context
                ->with(Outboxed::Outboxed)
                ->with($sender, Sender::class)
                ->with($publisher, Publisher::class)
                ->with($transaction->wrappedTransaction);

            $result = $pipeline->continue($context);

            $transaction->insertOutbox(
                new Outbox(
                    endpoint: $endpoint,
                    incomingMessageId: $envelope->messageId,
                    commands: $sender->commands,
                    events: $publisher->events,
                ),
            );
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }

        $transaction->commit();

        $originalSender->send(...$sender->commands);
        $originalPublisher->publish(...$publisher->events);

        $this->storage->completeOutbox($endpoint, $envelope->messageId);

        return $result;
    }
}
