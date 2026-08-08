<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
final readonly class ClassBasedTypeResolver implements TypeResolver
{
    /**
     * @param non-empty-string $namespaceSeparator
     */
    public function __construct(
        private string $namespaceSeparator = '.',
    ) {}

    public function typeOf(string $messageClass): string
    {
        return str_replace('\\', $this->namespaceSeparator, $messageClass);
    }
}
