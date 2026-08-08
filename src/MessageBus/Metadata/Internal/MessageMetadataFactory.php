<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\Internal;

use Thesis\MessageBus\Metadata\InvalidKind;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Protocol\InvalidType;
use Thesis\MessageBus\Protocol\TypeResolver;

/**
 * @internal
 */
final class MessageMetadataFactory
{
    public function __construct(
        private readonly MessageClassifier $classifier,
        private readonly TypeResolver $typeResolver,
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
                ?? throw new InvalidKind(\sprintf(
                    'Message class "%s" does not have a kind.',
                    $messageClass,
                )),
            type: $this->typeResolver->typeOf($messageClass)
                ?? throw new InvalidType(\sprintf(
                    'Message class "%s" must have a message type.',
                    $messageClass,
                )),
        );
    }
}
