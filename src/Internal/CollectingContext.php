<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;

/**
 * @internal
 * @template TTransaction of object
 * @extends Context<TTransaction>
 */
final class CollectingContext extends Context
{
    /**
     * @param TTransaction $transaction
     * @param Envelope<*> $envelope
     */
    public function __construct(
        public readonly Endpoint $endpoint,
        public readonly object $transaction,
        EnvelopeFactory $envelopeFactory,
        Envelope $envelope,
    ) {
        parent::__construct($envelopeFactory, $envelope);
    }

    /**
     * @var list<Envelope>
     */
    public private(set) array $commands = [];

    protected function doSend(array $commands): void
    {
        $this->commands = [...$this->commands, ...$commands];
    }

    /**
     * @var list<Envelope>
     */
    public private(set) array $events = [];

    protected function doPublish(array $events): void
    {
        $this->events = [...$this->events, ...$events];
    }

    protected function doInvoke(Envelope $call, Context $parentContext): mixed
    {
        throw new \LogicException();
        // return $this->dispatcher->dispatchCall($call, $parentContext);
    }

    public function close(): void
    {
        $this->events = [];
        $this->commands = [];

        // todo bool closed
    }
}
