<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

use Thesis\Message\Message;

/**
 * @api
 */
interface MessageClassEncoder
{
    /**
     * @param class-string<Message> $class
     */
    public function encodeMessageClass(string $class): string;
}
