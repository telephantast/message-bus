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
final class Envelope
{
    /**
     * @var class-string<T>
     */
    public string $class { get => $this->metadata->class; }

    public bool $isCommand { get => $this->metadata->isCommand; }

    public bool $isEvent { get => $this->metadata->isEvent; }

    /**
     * @param T $payload
     * @param Metadata<T> $metadata
     */
    public function __construct(
        public readonly object $payload,
        public readonly Metadata $metadata,
        public readonly TimeSpan $delay = new TimeSpan(),
    ) {
        if ($this->metadata->class !== $this->payload::class) {
            throw new \InvalidArgumentException();
        }
    }
}
