<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler\Parameter;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\CallableHandler\Parameter;

final readonly class Message implements Parameter
{
    public static function tryFrom(\ReflectionParameter $parameter): ?static
    {
        $messageClasses = self::parseMessageClasses($parameter->getType());

        if ($messageClasses === []) {
            return null;
        }

        return new self($messageClasses);
    }

    /**
     * @return list<class-string<\Thesis\Message\Message>>
     */
    private static function parseMessageClasses(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType
            && !$type->isBuiltin()
            && is_a($type->getName(), \Thesis\Message\Message::class, allow_string: true)
        ) {
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(self::parseMessageClasses(...), $type->getTypes()));
        }

        return [];
    }

    /**
     * @param non-empty-list<class-string<\Thesis\Message\Message>> $classes
     */
    private function __construct(
        public array $classes,
    ) {}

    public function resolveArgument(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        return $envelope->message;
    }
}
