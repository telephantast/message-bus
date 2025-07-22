<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Context\MessageCollector;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;

/**
 * @template TTransaction of object
 * @implements Invoker<object>
 */
final class Context implements Sender, Publisher, Invoker
{
    /**
     * @param callable(): TTransaction $transactionFactory
     */
    public function __construct(
        public Endpoint $endpoint,
        private readonly Envelope $envelope,
        private readonly mixed $transactionFactory,
        private readonly EnvelopeFactory $envelopeFactory,
        private readonly MessageCollector $outboxBuilder,
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * @var TTransaction
     */
    public object $transaction { get => ($this->transactionFactory)(); }

    public function send(object ...$commands): void
    {
        $this->outboxBuilder->addCommands(array_map($this->createEnvelope(...), $commands));
    }

    public function publish(object ...$events): void
    {
        $this->outboxBuilder->addEvents(array_map($this->createEnvelope(...), $events));
    }

    public function invoke(object $call): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($call), $this->envelopeFactory, $this);
    }

    /**
     * @template TMessage of object
     * @param TMessage|Envelope<TMessage> $message
     * @return Envelope<TMessage>
     */
    private function createEnvelope(object $message): Envelope
    {
        return $this->envelopeFactory->create($this->endpoint, $message, $this->envelope);
    }

    public function next(Endpoint $endpoint, Envelope $envelope): static
    {
        return new self(
            endpoint: $endpoint,
            envelope: $envelope,
            transactionFactory: $this->transactionFactory,
            envelopeFactory: $this->envelopeFactory,
            outboxBuilder: $this->outboxBuilder,
            dispatcher: $this->dispatcher,
        );
    }

    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return TResult
     */
    public function processResult(Result $result): mixed
    {
        $this->send(...$result->commands);
        $this->publish(...$result->events);

        return $result->result;
    }
}
