<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class AttributeMessageClassifier implements MessageClassifier
{
    public function kindOf(string $messageClass): ?MessageKind
    {
        $attributes = new \ReflectionClass($messageClass)->getAttributes(
            name: MessageAttribute::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        return match (\count($attributes)) {
            0 => null,
            1 => array_first($attributes)->newInstance()->kind,
            default => throw new InvalidMetadata(\sprintf(
                'Message class "%s" must have exactly one message attribute.',
                $messageClass,
            )),
        };
    }
}
