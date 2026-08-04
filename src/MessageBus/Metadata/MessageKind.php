<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
enum MessageKind
{
    case Command;
    case Event;
    case Reply;
}
