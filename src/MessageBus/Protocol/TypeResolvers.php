<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
final readonly class TypeResolvers implements TypeResolver
{
    /**
     * @param list<TypeResolver> $resolvers
     */
    public function __construct(
        private array $resolvers,
    ) {}

    public function typeOf(string $messageClass): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $type = $resolver->typeOf($messageClass);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }
}
