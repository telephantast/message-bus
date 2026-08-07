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
}
