<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
enum Disposition
{
    case Ack;
    case Requeue;
}
