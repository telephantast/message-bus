<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Envelope
{
    /**
     * @param T $payload
     */
    public function __construct(
        public object $payload,
        public Metadata $metadata,
    ) {}
}
