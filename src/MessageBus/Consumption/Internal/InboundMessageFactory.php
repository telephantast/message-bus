<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Protocol\DeserializationFailed;
use Thesis\MessageBus\Protocol\Deserializer;
use Thesis\MessageBus\Protocol\InvalidType;
use Thesis\MessageBus\Protocol\SerializedMessage;
use Thesis\MessageBus\Transport\InboundEnvelope;
use const Thesis\MessageBus\Protocol\CONTENT_ENCODING;
use const Thesis\MessageBus\Protocol\CONTENT_TYPE;
use const Thesis\MessageBus\Protocol\MESSAGE_TYPE;

/**
 * @internal
 */
final readonly class InboundMessageFactory
{
    /**
     * @param list<class-string> $knownClasses
     */
    public static function fromClasses(
        array $knownClasses,
        MessageMetadataFactory $messageMetadataFactory,
        Deserializer $deserializer,
    ): self {
        $typeMap = [];

        foreach (array_unique($knownClasses) as $messageClass) {
            $type = $messageMetadataFactory->forClass($messageClass)->type;

            if (isset($typeMap[$type])) {
                throw new InvalidType(\sprintf(
                    'Message type "%s" is used by both "%s" and "%s".',
                    $type,
                    $typeMap[$type],
                    $messageClass,
                ));
            }

            $typeMap[$type] = $messageClass;
        }

        return new self($typeMap, $deserializer);
    }

    /**
     * @param array<non-empty-string, class-string> $typeMap
     */
    public function __construct(
        private array $typeMap,
        private Deserializer $deserializer,
    ) {}

    public function build(InboundEnvelope $envelope): object
    {
        $headers = $envelope->headers;

        return $this->deserializer->deserialize(
            serializedMessage: new SerializedMessage(
                payload: $envelope->payload,
                contentType: $headers->find(CONTENT_TYPE),
                contentEncoding: $headers->find(CONTENT_ENCODING),
            ),
            messageClass: $this->resolveClass($headers->get(MESSAGE_TYPE)),
        );
    }

    /**
     * @param non-empty-string $type
     * @return class-string
     */
    private function resolveClass(string $type): string
    {
        return $this->typeMap[$type]
            ?? throw new DeserializationFailed(\sprintf('Unknown message type "%s"', $type));
    }
}
