<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

/**
 * @internal
 */
final class ChildContext extends Context
{
    /**
     * @param Envelope<*> $envelope
     */
    public function __construct(
        private readonly Context $parent,
        EnvelopeFactory $envelopeFactory,
        Envelope $envelope,
    ) {
        parent::__construct($envelopeFactory, $envelope);
    }

    public string $endpoint { get => $this->parent->endpoint; }

    public object $transaction { get => $this->parent->transaction; }

    protected function doSend(array $commands): void
    {
        $this->parent->doSend($commands);
    }

    protected function doPublish(array $events): void
    {
        $this->parent->doPublish($events);
    }

    protected function doInvoke(Envelope $call, Context $parentContext): mixed
    {
        return $this->parent->doInvoke($call, $parentContext);
    }
}
