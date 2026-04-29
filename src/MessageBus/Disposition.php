<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
enum Disposition
{
    case Ack;
    case Retry;
    case Reject;
}
