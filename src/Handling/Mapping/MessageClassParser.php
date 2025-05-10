<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Mapping;

use Thesis\Message\Message;
use function Typhoon\Formatter\formatReflectedFunction;
use function Typhoon\Formatter\formatReflectedParameter;
use function Typhoon\Formatter\formatReflectedType;

/**
 * @api
 */
final readonly class MessageClassParser
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

        $name = $type->getName();

        if (!class_exists($name)) {
            return [];
        }

        $class = new \ReflectionClass($name);

        if ($class->implementsInterface(Message::class) && $class->isFinal()) {
            /** @phpstan-ignore return.type */
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
                formatReflectedType($type),
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
            throw new \LogicException(\sprintf('%s must not be variadic', formatReflectedParameter($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', formatReflectedParameter($parameter)));
        }

        return self::fromType(formatReflectedFunction($parameter->getDeclaringFunction()), $parameter->getType());
    }

    /** @psalm-suppress UnusedConstructor */
    private function __construct() {}
}
