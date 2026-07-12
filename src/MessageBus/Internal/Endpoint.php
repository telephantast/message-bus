<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\ConsumerRuntime;
use Thesis\MessageBus\ConsumerRuntime\Consumer;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\Listeners;
use Thesis\MessageBus\Metadata\Kind;
use Thesis\MessageBus\OutgoingEnvelope;

/**
 * @internal
 *
 * @template Tx of object
 */
final class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param ConsumerRuntime<Tx> $consumerRuntime
     * @param Handlers<Tx> $handlers
     * @param Listeners<Tx> $listeners
     */
    public function __construct(
        private readonly string $name,
        private readonly Handlers $handlers,
        private readonly Listeners $listeners,
        private readonly ConsumerRuntime $consumerRuntime,
        private readonly EnvelopeFactory $envelopeFactory,
    ) {}

    /**
     * @var list<class-string>
     */
    public array $subscribedTo { get => $this->listeners->payloadClasses; }

    public function consume(Envelope $envelope): void
    {
        $this->consumerRuntime->consume($this->name, $envelope, $this->handle(...));
    }

    public function startConsumer(): Consumer
    {
        return $this->consumerRuntime->startConsumer($this->name, $this->handle(...));
    }

    /**
     * @param Tx $transaction
     * @return list<OutgoingEnvelope>
     */
    private function handle(Envelope $envelope, object $transaction): array
    {
        $context = new Context(
            endpoint: $this->name,
            transaction: $transaction,
        );

        match ($envelope->metadata->kind) {
            Kind::Command, Kind::Reply => $this->handlers->handle($envelope, $context),
            Kind::Event => $this->listeners->on($envelope, $context),
        };

        return array_map(
            fn(object $message) => $this->envelopeFactory->buildOutgoing($message, $envelope->metadata),
            $context->outgoingMessages,
        );
    }
}
