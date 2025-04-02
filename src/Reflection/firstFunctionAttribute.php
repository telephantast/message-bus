<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Reflection;

/**
 * @internal
 * @psalm-internal Thesis
 * @template T of object
 * @param class-string<T> $class
 * @return ?\ReflectionAttribute<T>
 */
function firstFunctionAttribute(\ReflectionFunctionAbstract $reflection, string $class): ?object
{
    $reflectionAttributes = $reflection->getAttributes($class, \ReflectionAttribute::IS_INSTANCEOF);

    if ($reflectionAttributes !== []) {
        return $reflectionAttributes[0];
    }

    if (!$reflection instanceof \ReflectionMethod) {
        return null;
    }

    try {
        return firstFunctionAttribute($reflection->getPrototype(), $class);
    } catch (\ReflectionException) {
        return null;
    }
}
