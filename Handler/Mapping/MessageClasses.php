<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler\Mapping;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Reflection\ReflectionStringifier;

/**
 * @api
 * @psalm-immutable
 */
enum MessageClasses
{
    /**
     * @return list<class-string<Message>>
     */
    public static function tryFromType(?\ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            return array_merge(
                ...array_map(
                    static fn(\ReflectionType $childType): array => self::tryFromType($childType),
                    $type->getTypes(),
                ),
            );
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException(\sprintf('%s is not supported', $type::class));
        }

        if ($type->isBuiltin()) {
            return [];
        }

        $class = new \ReflectionClass($type->getName());

        if ($class->implementsInterface(Message::class) && $class->isFinal()) {
            return [$class->name];
        }

        return [];
    }

    /**
     * @return non-empty-list<class-string<Message>>
     */
    public static function fromType(string $declaration, ?\ReflectionType $type): array
    {
        $messageClasses = self::tryFromType($type);

        if ($messageClasses === []) {
            throw new \LogicException(\sprintf(
                '%s type must be a union of %s final implementations, got %s.',
                $declaration,
                Message::class,
                ReflectionStringifier::type($type),
            ));
        }

        return $messageClasses;
    }

    /**
     * @return non-empty-list<class-string<Message>>
     */
    public static function fromParameter(\ReflectionParameter $parameter): array
    {
        if ($parameter->isVariadic()) {
            throw new \LogicException(\sprintf('%s must not be variadic', ReflectionStringifier::parameter($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', ReflectionStringifier::parameter($parameter)));
        }

        return self::fromType(ReflectionStringifier::parameter($parameter), $parameter->getType());
    }
}
