<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;

/**
 * @template TTransaction of object
 */
final readonly class AsyncHandler
{
    /**
     * @param Storage<TTransaction> $storage
     * @param callable(Envelope, Context<object, TTransaction>): void $handler
     */
    public function __construct(
        private Endpoint $endpoint,
        private mixed $handler,
        private Storage $storage,
        private string $persistenceKey,
        private Wrapper $wrapper,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param non-empty-list<Envelope> $envelopes
     */
    public function __invoke(array $envelopes): void
    {
        $outboxes = $this->storage->findOutboxes(
            endpoint: $this->endpoint,
            incomingMessageIds: array_column($envelopes, 'messageId'),
        );

        if (\count($outboxes) >= \count($envelopes)) {
            $this->dispatchOutboxes($outboxes);

            return;
        }

        $outboxesById = array_column($outboxes, null, 'incomingMessageId');
        $outboxesToRecord = [];

        $transaction = $this->storage->beginTransaction($this->endpoint);

        try {
            foreach ($envelopes as $envelope) {
                if (isset($outboxesById[$envelope->messageId])) {
                    continue;
                }

                $context = new Context(
                    endpoint: $this->endpoint,
                    transaction: $transaction->transaction,
                    persistenceKey: $this->persistenceKey,
                    invoke: $this->dispatcher,
                    wrapper: $this->wrapper,
                );

                ($this->handler)($envelope, $context);

                $outboxesToRecord[] = $outboxes[] = new Outbox(
                    incomingMessageId: $envelope->messageId,
                    commands: $context->commands,
                    events: $context->events,
                );
            }

            \assert($outboxesToRecord !== []);
            $transaction->recordOutboxes($outboxesToRecord);

            $transaction->commitIfBegun();
        } catch (\Throwable $exception) {
            $transaction->rollbackIfBegun();

            throw $exception;
        }

        $this->dispatchOutboxes($outboxes);
    }

    /**
     * @param list<Outbox> $outboxes
     */
    private function dispatchOutboxes(array $outboxes): void
    {
        $incomingMessageIdsToMarkSent = [];
        $commandsToSend = [];
        $eventsToPublish = [];

        foreach ($outboxes as $outbox) {
            if (!$outbox->dispatched) {
                $incomingMessageIdsToMarkSent[] = $outbox->incomingMessageId;
                $commandsToSend = [...$commandsToSend, ...$outbox->commands];
                $eventsToPublish = [...$eventsToPublish, ...$outbox->events];
            }
        }

        if ($incomingMessageIdsToMarkSent === []) {
            return;
        }

        if ($commandsToSend !== []) {
            $this->dispatcher->send($commandsToSend);
        }

        if ($eventsToPublish !== []) {
            $this->dispatcher->publish($eventsToPublish);
        }

        $this->storage->markOutboxesDispatched($this->endpoint, $incomingMessageIdsToMarkSent);
    }
}
