<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\Internal;

use Thesis\MessageBus\Metadata\MessageKind;

/**
 * @internal
 */
final readonly class MessageMetadata
{
    /**
     * @param non-empty-string $type
     */
    public function __construct(
        public MessageKind $kind,
        public string $type,
    ) {}
}
