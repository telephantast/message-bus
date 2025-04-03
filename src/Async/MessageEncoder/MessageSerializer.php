<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\MessageEncoder;

use Thesis\Message\Message;

/**
 * @api
 * @todo Move to testing?
 */
final readonly class MessageSerializer implements MessageNormalizer, MessageDenormalizer
{
    public function normalizeMessage(Message $message): mixed
    {
        return serialize($message);
    }

    public function denormalizeMessage(string $class, mixed $data): Message
    {
        if (!\is_string($data)) {
            throw new \LogicException('Failed to unserialize data into message');
        }

        $message = unserialize($data);

        if (!$message instanceof $class) {
            throw new \LogicException('Failed to unserialize data into message');
        }

        return $message;
    }
}
