<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\Mapping;

use Thesis\Message\Message;
use function Typhoon\Describe\describeReflectedDeclaration;
use function Typhoon\Describe\describeReflectedType;

/**
 * @api
 */
final readonly class MessageClasses
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
                describeReflectedType($type),
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
            throw new \LogicException(\sprintf('%s must not be variadic', describeReflectedDeclaration($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', describeReflectedDeclaration($parameter)));
        }

        return self::fromType(describeReflectedDeclaration($parameter), $parameter->getType());
    }

    /** @psalm-suppress UnusedConstructor */
    private function __construct() {}
}
