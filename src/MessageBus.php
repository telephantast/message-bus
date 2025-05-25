<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Internal\HandlingSession;
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
     * @param Storage<TTransaction> $storage
     * @param Handlers<TSupportedMessages, Message, TTransaction> $syncHandlers
     */
    public function __construct(
        private readonly Storage $storage,
        private readonly Handlers $syncHandlers,
    ) {}

    public function dispatchEnvelope(Envelope $envelope): mixed
    {
        $transaction = $this->storage->beginTransaction();

        try {
            $session = new HandlingSession($this->syncHandlers, $transaction->wrappedTransaction);
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
