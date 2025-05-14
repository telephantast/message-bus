<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Persistence\Outbox;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\StorageSetup;

/**
 * @template-contravariant TSupportedMessages of Message = Event
 * @template-covariant TTransaction of object = object
 * @implements Dispatcher<TSupportedMessages>
 */
final class MessageBus implements Dispatcher
{
    /**
     * @var array<non-empty-string, Handlers<never, TTransaction>>
     */
    private array $syncHandlers = [];

    /**
     * @var array<class-string<Message>, non-empty-string>
     */
    private array $syncRouting = [];

    /**
     * @param Storage<TTransaction> $storage
     * @param non-empty-string $defaultEndpoint
     */
    public function __construct(
        private readonly string $defaultEndpoint = 'default',
        private readonly Storage $storage = new InMemoryStorage(),
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param (\Closure(TMessage, HandlerContext<Message, TTransaction>): TResult)|Handler<TResult, TMessage, Message, TTransaction> $handler
     * @param ?non-empty-string $endpoint
     * @return self<TSupportedMessages|TMessage, TTransaction>
     */
    public function syncHandler(\Closure|Handler $handler, ?string $endpoint = null): self
    {
        if ($handler instanceof \Closure) {
            $handler = new CallableHandler($handler);
        }

        $endpoint ??= $this->defaultEndpoint;

        $messageBus = clone $this;

        foreach ($handler->messageClasses as $messageClass) {
            $messageBus->syncRouting[$messageClass] = $endpoint;
        }

        $messageBus->syncHandlers[$endpoint] = ($messageBus->syncHandlers[$endpoint] ?? $this->emptyHandlers())->with($handler);

        return $messageBus;
    }

    /**
     * @return Handlers<never, TTransaction>
     */
    private function emptyHandlers(): Handlers
    {
        /** @var Handlers<never, TTransaction> */
        return new Handlers();
    }

    private bool $setup = false;

    public function setup(): void
    {
        if (!$this->setup) {
            if ($this->storage instanceof StorageSetup) {
                $this->storage->setup();
            }

            $this->setup = true;
        }
    }

    /**
     * @template TResult
     * @param TSupportedMessages&Message<TResult> $message
     * @param list<Stamp> $stamps
     * @return TResult
     */
    public function dispatch(Message $message, array $stamps = [], ?HandlerContext $context = null): mixed
    {
        $this->setup();

        $endpoint = $this->syncRouting[$message::class] ?? throw new \Exception('Not routed');

        /** @var Handlers<Message<TResult>, TTransaction> */
        $handlers = $this->syncHandlers[$endpoint] ?? throw new \Exception('Not routed');

        if ($context !== null) {
            return $handlers->handle($message, $context);
        }

        $transaction = $this->storage->beginTransaction();

        try {
            $context = new HandlerContext($this, $transaction->wrappedTransaction);

            $result = $handlers->handle($message, $context);
            $transaction->commit(new Outbox($endpoint, bin2hex(random_bytes(10)), []));

            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback();

            throw $exception;
        }
    }
}
