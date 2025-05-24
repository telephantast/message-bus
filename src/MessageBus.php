<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Internal\HandlingSession;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;

/**
 * @template-contravariant TSupportedMessages of Message = \Thesis\Message\Event
 * @template-covariant TTransaction of object = object
 * @extends Dispatcher<TSupportedMessages>
 */
final class MessageBus extends Dispatcher
{
    /**
     * @param Handlers<TSupportedMessages, Message, TTransaction> $handlers
     * @param Storage<TTransaction> $storage
     */
    public function __construct(
        private readonly Storage $storage = new InMemoryStorage(),
        private readonly Handlers $handlers = new Handlers(),
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param (\Closure(TMessage, Dispatcher<Message>, Stamps, TTransaction): TResult)|Handler<TMessage, Message, TTransaction> $handler
     * @return self<TSupportedMessages|TMessage, TTransaction>
     */
    public function withHandler(\Closure|Handler $handler): self
    {
        if ($handler instanceof \Closure) {
            $handler = new CallableHandler($handler);
        }

        return new self(
            storage: $this->storage,
            handlers: $this->handlers->with($handler),
        );
    }

    public function dispatchEnvelope(Envelope $envelope): mixed
    {
        $transaction = $this->storage->beginTransaction();

        try {
            /** @phpstan-ignore argument.type */
            $session = new HandlingSession($this->handlers, $transaction);
            /** @phpstan-ignore argument.type */
            $result = $session->dispatchEnvelope($envelope);
            $session->dispatchPostponed();
            $transaction->commit(new Outbox('default', bin2hex(random_bytes(10)), []));
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }

        return $result;
    }
}
