<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

use Thesis\Message\Message;

/**
 * @api
 */
final readonly class PassthroughClassEncoder implements MessageClassEncoder
{
    public function encodeMessageClass(string $class): string
    {
        return $class;
    }

    public function decodeMessageClass(string $encodedMessageClass): string
    {
        if (!is_a($encodedMessageClass, Message::class, allow_string: true)) {
            throw new \LogicException();
        }

        return $encodedMessageClass;
    }
}
