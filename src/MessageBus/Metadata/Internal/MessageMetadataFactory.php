<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\Internal;

use Thesis\MessageBus\Metadata\InvalidMetadata;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageTypeResolver;

/**
 * @internal
 */
final class MessageMetadataFactory
{
    public function __construct(
        private readonly MessageClassifier $classifier,
        private readonly MessageTypeResolver $typeResolver,
    ) {}

    /**
     * @var array<class-string, MessageMetadata>
     */
    private array $metadata = [];

    /**
     * @param class-string $messageClass
     */
    public function forClass(string $messageClass): MessageMetadata
    {
        return $this->metadata[$messageClass] ??= new MessageMetadata(
            kind: $this->classifier->kindOf($messageClass)
                ?? throw new InvalidMetadata(\sprintf(
                    'Message class "%s" must be marked as #[Command], #[Event], or #[Reply].',
                    $messageClass,
                )),
            type: $this->typeResolver->typeOf($messageClass)
                ?? throw new InvalidMetadata(\sprintf(
                    'Message class "%s" must have a message type.',
                    $messageClass,
                )),
        );
    }
}
