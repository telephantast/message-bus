<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * For testing purposes only.
 */
final class IncrementalMessageIdGenerator implements MessageIdGenerator
{
    public function __construct(
        private int $id = 1,
    ) {}

    public function generateMessageId(): string
    {
        $id = $this->id;
        ++$this->id;

        return (string) $id;
    }
}
