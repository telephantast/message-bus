<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 *
 * @phpstan-sealed Command|Event|Reply
 */
abstract class Message
{
    abstract public MessageKind $kind { get; }

    /**
     * @param ?non-empty-string $type
     */
    final public function __construct(
        public readonly ?string $type = null,
    ) {}
}
