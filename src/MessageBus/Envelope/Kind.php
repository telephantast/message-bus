<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
enum Kind: string
{
    case Command = 'command';
    case Event = 'event';
}
