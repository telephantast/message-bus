<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final class Outbox
{
    public private(set) bool $dispatched;

    /**
     * @param non-empty-string $incomingMessageId
     * @param list<Envelope> $commands
     * @param list<Envelope> $events
     */
    public function __construct(
        public readonly string $incomingMessageId,
        public readonly array $commands,
        public readonly array $events,
        bool $dispatched = false,
    ) {
        $this->dispatched = $dispatched || ($commands === [] && $events === []);
    }

    public function toDispatched(): self
    {
        if ($this->dispatched) {
            return $this;
        }

        $outbox = clone $this;
        $outbox->dispatched = true;

        return $outbox;
    }
}
