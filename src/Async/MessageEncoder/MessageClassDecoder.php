<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

use Thesis\Message\Message;

/**
 * @api
 */
interface MessageClassDecoder
{
    /**
     * @return class-string<Message>
     */
    public function decodeMessageClass(string $encodedMessageClass): string;
}
