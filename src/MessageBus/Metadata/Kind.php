<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
enum Kind: string
{
    case Command = 'command';
    case Event = 'event';
    case Reply = 'reply';
}
