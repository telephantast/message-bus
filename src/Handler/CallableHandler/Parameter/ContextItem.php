<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler\Parameter;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\CallableHandler\FromContext;
use Thesis\MessageBus\Handler\CallableHandler\Parameter;

final readonly class ContextItem implements Parameter
{
    public static function tryFrom(\ReflectionParameter $parameter): ?static
    {
        if ($parameter->getAttributes(FromContext::class) === []) {
            return null;
        }

        $classes = self::parseClasses($parameter->getType());

        if ($classes === []) {
            throw new \LogicException();
        }

        return new self($classes);
    }

    /**
     * @return list<class-string>
     */
    private static function parseClasses(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            /** @var array{class-string} */
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(self::parseClasses(...), $type->getTypes()));
        }

        return [];
    }

    /**
     * @param non-empty-list<class-string> $classes
     */
    private function __construct(
        public array $classes,
    ) {}

    public function resolveArgument(string $endpoint, Envelope $envelope, Context $context): mixed
    {
        $items = array_filter(array_map($context->find(...), $this->classes));

        if (\count($items) === 1) {
            return $items[array_key_first($items)];
        }

        throw new \RuntimeException('Ambiguity');
    }
}
