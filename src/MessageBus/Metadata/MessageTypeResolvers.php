<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class MessageTypeResolvers implements MessageTypeResolver
{
    /**
     * @param list<MessageTypeResolver> $resolvers
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
