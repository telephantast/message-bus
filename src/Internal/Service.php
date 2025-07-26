<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Method;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;

/**
 * @internal
 * @template TTransaction of object
 */
final readonly class Service
{
    private Endpoint $endpoint;

    /**
     * @param non-empty-string $name
     * @param MethodHandlers<TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        string $name,
        private MethodHandlers $handlers,
        private Wrapper $wrapper,
        private Storage $storage,
        private string $persistenceKey,
    ) {
        $this->endpoint = Endpoint::service($name);
    }

    /**
     * @template TResult
     * @param ?Context<object, *> $parentContext
     * @return ($method is Envelope<Method<TResult>> ? TResult : mixed)
     */
    public function invoke(Dispatcher $dispatcher, Envelope $method, ?Context $parentContext): mixed
    {
        if ($parentContext !== null && $parentContext->persistenceKey === $this->persistenceKey) {
            /** @var Context<object, TTransaction> $parentContext */
            return $this->handlers->handle($method, $parentContext->child($this->endpoint, $method));
        }

        $lazyTransaction = $this->storage->createLazyTransaction($this->endpoint);

        try {
            $context = new Context(
                endpoint: $this->endpoint,
                transaction: $lazyTransaction->transaction,
                persistenceKey: $this->persistenceKey,
                wrapper: $this->wrapper->withCause($method),
                childInvoke: $dispatcher,
            );

            $result = $this->handlers->handle($method, $context);

            $outbox = new Outbox(
                incomingMessageId: $method->messageId,
                commands: $context->commands,
                events: $context->events,
            );

            if (!$outbox->dispatched) {
                // send service command

                $lazyTransaction->recordOutboxes([$outbox]);
            }

            $lazyTransaction->commitIfBegun();
        } catch (\Throwable $exception) {
            $lazyTransaction->rollbackIfBegun();

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
