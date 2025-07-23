<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Context\MessageCollector;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Method;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;

/**
 * @template TTransaction of object
 */
final readonly class Service
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param MethodHandlers<object> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        string $name,
        private MethodHandlers $handlers,
        private Storage $storage,
    ) {
        $this->endpoint = Endpoint::service($name);
    }

    /**
     * @template TResult
     * @param ?Context<*> $parentContext
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function invoke(Envelope $method, ?Context $parentContext, EnvelopeFactory $envelopeFactory, Dispatcher $dispatcher): mixed
    {
        /** @var \WeakMap<Context<*>, Storage<*>> */
        static $storages = new \WeakMap();

        if ($parentContext !== null && ($storages[$parentContext] ?? null) === $this->storage) {
            return $parentContext->child($this->endpoint, $method);
        }

        $transaction = new LazyTransaction($this->storage, $this->endpoint);

        try {
            $messageCollector = new MessageCollector();

            $result = $this->handlers->handle($method, new Context(
                endpoint: $this->endpoint,
                envelope: $method,
                transactionFactory: $transaction,
                envelopeFactory: $envelopeFactory,
                messageCollector: $messageCollector,
                dispatcher: $dispatcher,
            ));

            $outbox = new Outbox(
                incomingMessageId: $method->messageId,
                commands: $messageCollector->commands,
                events: $messageCollector->events,
            );

            if (!$outbox->dispatched) {
                // send service command

                $transaction->begin()->recordOutboxes([$outbox]);
            }

            $transaction->commitIfBegun();
        } catch (\Throwable $exception) {
            $transaction->rollbackIfBegun();

            throw $exception;
        }

        if (!$outbox->dispatched) {
            try {
                if ($outbox->commands !== []) {
                    $dispatcher->send($outbox->commands);
                }

                if ($outbox->events !== []) {
                    $dispatcher->publish($outbox->events);
                }

                $this->storage->markOutboxesDispatched($this->endpoint, [$method->messageId]);
            } catch (\Throwable) {
                // todo log
            }
        }

        return $result;
    }
}
