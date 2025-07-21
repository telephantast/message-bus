<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\EnvelopeFactory;

/**
 * @api
 * @implements Invoker<Call>
 * @template TTransaction of object
 */
abstract class Context implements Sender, Publisher, Invoker
{
    abstract public Endpoint $endpoint { get; }

    /**
     * @var TTransaction
     */
    abstract public object $transaction { get; }

    /**
     * @param Envelope<*> $envelope
     */
    public function __construct(
        private readonly EnvelopeFactory $envelopeFactory,
        protected readonly Envelope $envelope,
    ) {}

    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return TResult
     */
    final public function processResult(Result $result): mixed
    {
        $this->send(...$result->commands);
        $this->publish(...$result->events);

        return $result->result;
    }

    final public function send(object ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->doSend(array_map($this->createEnvelope(...), $commands));
    }

    final public function publish(object ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->doPublish(array_map($this->createEnvelope(...), $events));
    }

    final public function invoke(Call|Envelope $call): mixed
    {
        return $this->doInvoke($this->createEnvelope($call), $this);
    }

    /**
     * @param non-empty-list<Envelope> $commands
     */
    abstract protected function doSend(array $commands): void;

    /**
     * @param non-empty-list<Envelope> $events
     */
    abstract protected function doPublish(array $events): void;

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @param Context<*> $parentContext
     * @return TResult
     */
    abstract protected function doInvoke(Envelope $call, self $parentContext): mixed;

    /**
     * @template TMessage of object
     * @param TMessage|Envelope<TMessage> $message
     * @return Envelope<TMessage>
     */
    final protected function createEnvelope(object $message): Envelope
    {
        return $this->envelopeFactory->create($this->endpoint, $message, $this->envelope);
    }
}
