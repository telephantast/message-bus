<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
interface MessageTypeResolver
{
    /**
     * @param class-string $messageClass
     * @return non-empty-string|null null when the resolver cannot determine a message type
     */
    public function typeOf(string $messageClass): ?string;
}
