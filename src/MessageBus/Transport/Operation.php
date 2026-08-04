<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
enum Operation: string
{
    case Send = 'send';
    case Publish = 'publish';
}
