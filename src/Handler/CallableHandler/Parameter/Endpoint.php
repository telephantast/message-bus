<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler\Parameter;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\CallableHandler\Parameter;

enum Endpoint implements Parameter
{
    case Instance;

    public static function tryFrom(\ReflectionParameter $parameter): ?static
    {
        if ($parameter->name === 'endpoint' && self::typeHasString($parameter->getType())) {
            return self::Instance;
        }

        return null;
    }

    private static function typeHasString(?\ReflectionType $type): bool
    {
        if ($type instanceof \ReflectionNamedType && $type->getName() === 'string') {
            return true;
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), self::typeHasString(...));
        }

        return false;
    }

    public function resolveArgument(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        return $endpoint;
    }
}
