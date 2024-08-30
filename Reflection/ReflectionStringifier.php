<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Reflection;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus
 */
enum ReflectionStringifier
{
    public static function type(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'no type';
        }

        if ($type instanceof \ReflectionNamedType) {
            if ($type->allowsNull() && !\in_array($type->getName(), ['null', 'mixed'], true)) {
                return '?' . $type->getName();
            }

            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(self::type(...), $type->getTypes()));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(self::type(...), $type->getTypes()));
        }

        throw new \LogicException(\sprintf('%s is not supported', $type::class));
    }

    /**
     * @return non-empty-string
     */
    public static function function(\ReflectionFunctionAbstract $function): string
    {
        if ($function instanceof \ReflectionMethod) {
            return \sprintf('%s::%s()', $function->class, $function->name);
        }

        return $function->name . '()';
    }

    /**
     * @return non-empty-string
     */
    public static function parameter(\ReflectionParameter $parameter): string
    {
        return \sprintf('%s $%s', self::function($parameter->getDeclaringFunction()), $parameter->getName());
    }
}
