<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Context\MessageCollector;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\Dispatcher;
use Thesis\MessageBus\Internal\EnvelopeFactory;

/**
 * @template-contravariant TSupportedMethods of object = never
 * @template TTransaction of object = object
 * @implements Invoke<object>
 */
final class Context implements Invoke
{
    /**
     * @param callable(): TTransaction $transactionFactory
     */
    public function __construct(
        public Endpoint $endpoint,
        private readonly Envelope $envelope,
        private readonly mixed $transactionFactory,
        private readonly EnvelopeFactory $envelopeFactory,
        private readonly MessageCollector $messageCollector,
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * @var TTransaction
     */
    public object $transaction { get => ($this->transactionFactory)(); }

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        $this->messageCollector->addCommands(array_map($this->createEnvelope(...), $commands));
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        $this->messageCollector->addEvents(array_map($this->createEnvelope(...), $events));
    }

    /**
     * @template TResult
     * @param TSupportedMethods|Envelope<TSupportedMethods> $method
     * @return ($method is (Method<TResult>|Envelope<Method<TResult>>) ? TResult : mixed)
     */
    public function invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($method), $this->envelopeFactory, $this);
    }

    public function __invoke(object $method): mixed
    {
        return $this->dispatcher->invoke($this->createEnvelope($method), $this->envelopeFactory, $this);
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

    public function child(Endpoint $endpoint, Envelope $envelope): static
    {
        return new self(
            endpoint: $endpoint,
            envelope: $envelope,
            transactionFactory: $this->transactionFactory,
            envelopeFactory: $this->envelopeFactory,
            messageCollector: $this->messageCollector,
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
        $this->messageCollector->addCommands($result->commands);
        $this->messageCollector->addEvents($result->events);

        return $result->result;
    }
}
