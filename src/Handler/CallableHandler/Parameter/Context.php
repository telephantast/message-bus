<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler\Parameter;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\CallableHandler\Parameter;

enum Context implements Parameter
{
    case Instance;

    public static function tryFrom(\ReflectionParameter $parameter): ?static
    {
        if (self::typeHasContext($parameter->getType())) {
            return self::Instance;
        }

        return null;
    }

    private static function typeHasContext(?\ReflectionType $type): bool
    {
        if ($type instanceof \ReflectionNamedType && $type->getName() === \Thesis\MessageBus\Context::class) {
            return true;
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), self::typeHasContext(...));
        }

        return false;
    }

    public function resolveArgument(string $endpoint, Envelope $envelope, \Thesis\MessageBus\Context $context): mixed
    {
        return $context;
    }
}
