<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class AttributeMessageTypeResolver implements MessageTypeResolver
{
    public function typeOf(string $messageClass): ?string
    {
        $attributes = new \ReflectionClass($messageClass)->getAttributes(
            name: Message::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        return array_first($attributes)?->newInstance()?->type;
    }
}
