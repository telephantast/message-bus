<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
final readonly class Envelope
{
    public function __construct(
        public object $payload,
        public Metadata $metadata,
    ) {}
}
