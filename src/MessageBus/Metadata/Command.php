<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Command extends Message
{
    public MessageKind $kind { get => MessageKind::Command; }

    /**
     * @param ?non-empty-string $type
     * @param ?non-empty-string $destination Endpoint name
     */
    public function __construct(
        ?string $type = null,
        public readonly ?string $destination = null,
    ) {
        parent::__construct($type);
    }
}
