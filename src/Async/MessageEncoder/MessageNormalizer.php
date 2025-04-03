<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

use Thesis\Message\Message;

/**
 * @api
 */
interface MessageNormalizer
{
    public function normalizeMessage(Message $message): mixed;
}
