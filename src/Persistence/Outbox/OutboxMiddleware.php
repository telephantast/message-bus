<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Outbox;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\Context;
use Thesis\MessageBus\Handler\Endpoint;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Pipeline;
use Thesis\MessageBus\Result;

final readonly class OutboxMiddleware implements Middleware
{
    /**
     * @param OutboxStorage<*> $storage
     */
    public function __construct(
        private OutboxStorage $storage,
    ) {}

    public function handle(Envelope $envelope, Context $context, Pipeline $pipeline): Result
    {
        if ($context->has(Outboxed::class)) {
            return $pipeline->continue();
        }

        $endpoint = $context->get(Endpoint::class)->endpoint;

        $outbox = $this->storage->findOutbox(
            incomingMessageId: $envelope->messageId,
            endpoint: $endpoint,
        );

        if ($outbox !== null) {
            /** @phpstan-ignore return.type */
            return new Result(
                commands: $outbox->commands,
                events: $outbox->events,
            );
        }

        $transaction = $this->storage->beginTransaction();

        try {
            $context = $context
                ->with(Outboxed::Outboxed)
                ->with($transaction->wrappedTransaction);

            $result = $pipeline->continue($context);

            $transaction->insertOutbox(
                new Outbox(
                    incomingMessageId: $envelope->messageId,
                    endpoint: $endpoint,
                    commands: $result->commands,
                    events: $result->events,
                ),
            );
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }

        $transaction->commit();

        return $result;
    }
}
