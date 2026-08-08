<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

use Thesis\MessageBus\Protocol\TypeResolver;
use Thesis\MessageBus\Routing\CommandRouter;

/**
 * @api
 */
final readonly class AttributeConvention implements MessageClassifier, TypeResolver, CommandRouter
{
    public function kindOf(string $messageClass): ?MessageKind
    {
        return self::findMessageAttribute($messageClass)?->kind;
    }

    public function typeOf(string $messageClass): ?string
    {
        return self::findMessageAttribute($messageClass)?->type;
    }

    public function destinationFor(string $commandClass): ?string
    {
        return array_first(new \ReflectionClass($commandClass)->getAttributes(Command::class))
            ?->newInstance()
            ?->destination;
    }

    /**
     * @param class-string $messageClass
     */
    private static function findMessageAttribute(string $messageClass): ?Message
    {
        $attributes = new \ReflectionClass($messageClass)->getAttributes(
            name: Message::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        return match (\count($attributes)) {
            0 => null,
            1 => array_first($attributes)->newInstance(),
            default => throw new InvalidKind(\sprintf(
                'Message class "%s" must have exactly one kind attribute.',
                $messageClass,
            )),
        };
    }
}
