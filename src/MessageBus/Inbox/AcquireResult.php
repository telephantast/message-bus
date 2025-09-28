<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Inbox;

/**
 * @api
 */
enum AcquireResult
{
    case Acquired;
    case Locked;
    case Consumed;
}
