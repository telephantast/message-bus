<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler\Mapping\Id;

final readonly class HandlerReflector
{
    /**
     * @return ?non-empty-string
     */
    public static function reflectId(\ReflectionFunctionAbstract $reflection): ?string
    {
        $ids = $reflection->getAttributes(Id::class);

        if ($ids === []) {
            return null;
        }

        if (\count($ids) > 1) {
            throw new \LogicException();
        }

        return $ids[0]->newInstance()->id;
    }

    /**
     * @return list<MessageClass<*>>
     */
    public static function reflectMessagesClasses(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(self::reflectMessagesClasses(...), $type->getTypes()));
        }

        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            if (!is_a($name, Message::class, allow_string: true)) {
                return [];
            }

            return [MessageClass::from($name)];
        }

        // todo intersection
        return [];
    }
}
