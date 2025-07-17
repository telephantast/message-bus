<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;

/**
 * @api
 * @implements Invoker<Call>
 */
abstract class Context implements Sender, Publisher, Invoker
{
    /**
     * @var non-empty-string
     */
    abstract public string $endpoint { get; }

    abstract public object $transaction { get; }

    /**
     * @param Envelope<*> $envelope
     */
    public function __construct(
        private readonly OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor,
        private readonly Envelope $envelope,
    ) {}

    final public function send(Envelope|Command ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->doSend(array_map($this->prepareOutgoingEnvelope(...), $commands));
    }

    final public function publish(Event|Envelope ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->doPublish(array_map($this->prepareOutgoingEnvelope(...), $events));
    }

    final public function invoke(Call|Envelope $call): mixed
    {
        return $this->doInvoke($this->prepareOutgoingEnvelope($call), $this);
    }

    /**
     * @param non-empty-list<Envelope<Command>> $commands
     */
    abstract protected function doSend(array $commands): void;

    /**
     * @param non-empty-list<Envelope<Event>> $events
     */
    abstract protected function doPublish(array $events): void;

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    abstract protected function doInvoke(Envelope $call, self $parentContext): mixed;

    /**
     * @template TMessage of Message
     * @param TMessage|Envelope<TMessage> $envelope
     * @return Envelope<TMessage>
     */
    final protected function prepareOutgoingEnvelope(Message|Envelope $envelope): Envelope
    {
        return $this->outgoingEnvelopeProcessor->process($this->endpoint, Envelope::wrap($envelope), $this->envelope);
    }
}
