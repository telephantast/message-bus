<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway;

/**
 * @api
 */
enum Disposition
{
    case Ack;
    case Retry;
    case Reject;
}
