<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\InheritableContextAttribute;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 */
final class OutboxCollector implements InheritableContextAttribute
{
    /**
     * @var list<Envelope<null, Message<null>>>
     */
    private array $envelopes = [];

    /**
     * @param Envelope<null, Message<null>> $envelope
     */
    public function add(Envelope $envelope): void
    {
        $this->envelopes[] = $envelope;
    }

    /**
     * @phpstan-assert-if-false !empty $this->getEnvelopes()
     */
    public function isEmpty(): bool
    {
        return $this->envelopes === [];
    }

    /**
     * @return list<Envelope<null, Message<null>>>
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }
}
