<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class ClassBasedMessageTypeResolver implements MessageTypeResolver
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
