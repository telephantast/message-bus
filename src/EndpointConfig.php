<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\MessageMatcher\Boolean;
use Thesis\MessageBus\Persistence\InMemoryStorage;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Transport\CallClient;
use Thesis\MessageBus\Transport\CallServer;
use Thesis\MessageBus\Transport\CommandReceiver;
use Thesis\MessageBus\Transport\CommandSender;
use Thesis\MessageBus\Transport\EventPublisher;
use Thesis\MessageBus\Transport\EventReceiver;
use Thesis\MessageBus\Transport\Fake;

final readonly class EndpointConfig
{
    public CommandSender $commandSender;

    public CommandReceiver $commandReceiver;

    public EventPublisher $eventPublisher;

    public EventReceiver $eventReceiver;

    public CallClient $callClient;

    public CallServer $callServer;

    public function __construct(
        public Handlers $handlers = new Handlers(),
        public MessageMatcher $handlesCommand = Boolean::False,
        public MessageMatcher $publishesEvent = Boolean::False,
        public MessageMatcher $handlesCall = Boolean::False,
        public Storage $storage = new InMemoryStorage(),
        CommandSender|CommandReceiver|EventPublisher|EventReceiver|CallClient|CallServer $transport = Fake::Instance,
        ?CommandSender $commandSender = null,
        ?CommandReceiver $commandReceiver = null,
        ?EventPublisher $eventPublisher = null,
        ?EventReceiver $eventReceiver = null,
        ?CallClient $callClient = null,
        ?CallServer $callServer = null,
    ) {
        $this->commandSender = $commandSender ?? ($transport instanceof CommandSender ? $transport : Fake::Instance);
        $this->commandReceiver = $commandReceiver ?? ($transport instanceof CommandReceiver ? $transport : Fake::Instance);
        $this->eventPublisher = $eventPublisher ?? ($transport instanceof EventPublisher ? $transport : Fake::Instance);
        $this->eventReceiver = $eventReceiver ?? ($transport instanceof EventReceiver ? $transport : Fake::Instance);
        $this->callClient = $callClient ?? ($transport instanceof CallClient ? $transport : Fake::Instance);
        $this->callServer = $callServer ?? ($transport instanceof CallServer ? $transport : Fake::Instance);
    }
}
