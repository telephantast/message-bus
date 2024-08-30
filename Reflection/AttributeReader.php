<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Reflection;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus
 */
enum AttributeReader
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ?\ReflectionAttribute<T>
     */
    public static function firstAttribute(
        \ReflectionClass|\ReflectionFunctionAbstract|\ReflectionParameter $reflection,
        string $class,
    ): ?object {
        $reflectionAttributes = $reflection->getAttributes($class, \ReflectionAttribute::IS_INSTANCEOF);

        if ($reflectionAttributes !== []) {
            return $reflectionAttributes[0];
        }

        if (!$reflection instanceof \ReflectionMethod) {
            return null;
        }

        try {
            return self::firstAttribute($reflection->getPrototype(), $class);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
