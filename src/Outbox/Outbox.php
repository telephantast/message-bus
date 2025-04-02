<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\InheritableContextAttribute;

/**
 * @api
 */
final class Outbox implements InheritableContextAttribute
{
    /**
     * @var list<Envelope<mixed, Message<mixed>>>
     */
    private array $envelopes = [];

    /**
     * @phpstan-assert-if-false !empty $this->getEnvelopes()
     */
    public function isEmpty(): bool
    {
        return $this->envelopes === [];
    }

    /**
     * @return list<Envelope<mixed, Message<mixed>>>
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }

    /**
     * @param Envelope<mixed, Message<mixed>> $envelope
     */
    public function add(Envelope $envelope): void
    {
        $this->envelopes[] = $envelope;
    }
}
