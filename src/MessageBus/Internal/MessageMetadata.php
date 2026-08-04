<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Metadata\MessageKind;

/**
 * @internal
 *
 * @template-covariant T of object = object
 */
final class MessageMetadata
{
    /**
     * @param class-string<T> $class
     * @param ?non-empty-string $destinationEndpoint
     * @param non-empty-string $type
     */
    public function __construct(
        public readonly string $class,
        public readonly MessageKind $kind,
        public readonly string $type,
        public readonly ?string $destinationEndpoint = null,
    ) {}

    public bool $isEvent { get => $this->kind === MessageKind::Event; }

    public bool $isCommand { get => $this->kind === MessageKind::Command; }

    public bool $isReply { get => $this->kind === MessageKind::Reply; }
}
