<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Metadata\CommandRouter;
use Thesis\MessageBus\Metadata\InvalidMetadata;
use Thesis\MessageBus\Metadata\MessageClassifier;
use Thesis\MessageBus\Metadata\MessageKind;
use Thesis\MessageBus\Metadata\MessageTypeResolver;

/**
 * @internal
 */
final class MessageMetadataRegistry
{
    public function __construct(
        private readonly MessageClassifier $classifier,
        private readonly MessageTypeResolver $typeResolver,
        private readonly CommandRouter $commandRouter,
    ) {}

    /**
     * @var array<class-string, MessageMetadata>
     */
    private array $byClass = [];

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return MessageMetadata<T>
     */
    public function forClass(string $messageClass): MessageMetadata
    {
        return $this->byClass[$messageClass] ??= $this->create($messageClass);
    }

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return MessageMetadata<T>
     */
    private function create(string $messageClass): MessageMetadata
    {
        $kind = $this->classifier->kindOf($messageClass) ?? throw new InvalidMetadata(\sprintf(
            'Message class "%s" must be marked as #[Command], #[Event], or #[Reply].',
            $messageClass,
        ));

        return new MessageMetadata(
            class: $messageClass,
            kind: $kind,
            type: $this->typeResolver->typeOf($messageClass) ?? throw new InvalidMetadata(\sprintf(
                'Message class "%s" must have a message type.',
                $messageClass,
            )),
            destinationEndpoint: match ($kind) {
                MessageKind::Command => $this->commandRouter->route($messageClass),
                default => null,
            },
        );
    }
}
