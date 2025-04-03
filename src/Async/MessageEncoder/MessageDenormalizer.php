<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

use Thesis\Message\Message;

/**
 * @api
 */
interface MessageDenormalizer
{
    /**
     * @template TMessage of Message
     * @param class-string<TMessage> $class
     */
    public function denormalizeMessage(string $class, mixed $data): Message;
}
