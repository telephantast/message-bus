<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler\Parameter;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\CallableHandler\Parameter;

enum Stamps implements Parameter
{
    case Instance;

    public static function tryFrom(\ReflectionParameter $parameter): ?static
    {
        if (self::typeHasStamps($parameter->getType())) {
            return self::Instance;
        }

        return null;
    }

    private static function typeHasStamps(?\ReflectionType $type): bool
    {
        if ($type instanceof \ReflectionNamedType && $type->getName() === \Thesis\MessageBus\Stamps::class) {
            return true;
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), self::typeHasStamps(...));
        }

        return false;
    }

    public function resolveArgument(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        return $envelope->stamps;
    }
}
