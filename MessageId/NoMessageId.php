<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\Message\Message;

/**
 * @api
 */
final class NoMessageId extends \RuntimeException
{
    /**
     * @param class-string<Message> $messageClass
     */
    public function __construct(
        public readonly string $messageClass,
    ) {
        parent::__construct(sprintf('Message %s is not identified via the %s stamp', $messageClass, MessageId::class));
    }
}
