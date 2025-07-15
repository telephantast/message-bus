<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessors;
use Thesis\MessageBus\Handler\Handlers;
use Thesis\MessageBus\MessageMatcher\Boolean;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Tracing\AddCauseIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddConversationIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddMessageIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddTimestampToOutgoingEnvelope;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\EventReceiver;
use Thesis\MessageBus\Transport\Fake;

final readonly class EndpointConfig
{
    public OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor;

    public CommandSender $commandSender;

    public CommandReceiver $commandReceiver;

    public EventPublisher $eventPublisher;

    public EventReceiver $eventReceiver;

    /**
     * @param Handler<*> $handler
     * @param list<OutgoingEnvelopeProcessor> $outgoingEnvelopeProcessors
     */
    public function __construct(
        public Handler $handler = new Handlers(),
        public MessageMatcher $handlesCommand = Boolean::False,
        public MessageMatcher $publishesEvent = Boolean::False,
        public MessageMatcher $handlesCall = Boolean::False,
        public Storage $storage = new InMemoryStorage(),
        array $outgoingEnvelopeProcessors = [
            new AddTimestampToOutgoingEnvelope(),
            new AddMessageIdToOutgoingEnvelope(),
            new AddConversationIdToOutgoingEnvelope(),
            new AddCauseIdToOutgoingEnvelope(),
        ],
        CommandSender|CommandReceiver|EventPublisher|EventReceiver $transport = Fake::Instance,
        ?CommandSender $commandSender = null,
        ?CommandReceiver $commandReceiver = null,
        ?EventPublisher $eventPublisher = null,
        ?EventReceiver $eventReceiver = null,
    ) {
        $this->outgoingEnvelopeProcessor = new OutgoingEnvelopeProcessors($outgoingEnvelopeProcessors);
        $this->commandSender = $commandSender ?? ($transport instanceof CommandSender ? $transport : Fake::Instance);
        $this->commandReceiver = $commandReceiver ?? ($transport instanceof CommandReceiver ? $transport : Fake::Instance);
        $this->eventPublisher = $eventPublisher ?? ($transport instanceof EventPublisher ? $transport : Fake::Instance);
        $this->eventReceiver = $eventReceiver ?? ($transport instanceof EventReceiver ? $transport : Fake::Instance);
    }
}
