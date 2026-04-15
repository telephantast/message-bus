<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Envelope\Metadata;
use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Envelope
{
    /**
     * @param Metadata<T> $metadata
     * @param T $payload
     */
    public function __construct(
        public Metadata $metadata,
        public object $payload,
        public TimeSpan $delay = new TimeSpan(),
    ) {
        if ($this->metadata->class !== $this->payload::class) {
            throw new \InvalidArgumentException(\sprintf(
                'Metadata class `%s` is different from payload class `%s`',
                $this->metadata->class,
                $this->payload::class,
            ));
        }
    }
}
