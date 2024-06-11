<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\InheritableContextAttribute;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus\Outbox
 */
final class Outbox implements InheritableContextAttribute
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var list<Envelope>
     */
    public array $envelopes = [];

    public function add(Envelope $envelope): void
    {
        $this->envelopes[] = $envelope;
    }
}
