<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Async\MessageEncoder\DataEncoder;
use Thesis\MessageBus\Async\MessageEncoder\JsonDataEncoder;
use Thesis\MessageBus\Async\MessageEncoder\MessageClassEncoder;
use Thesis\MessageBus\Async\MessageEncoder\MessageNormalizer;
use Thesis\MessageBus\Async\MessageEncoder\MessageSerializer;
use Thesis\MessageBus\Async\MessageEncoder\PassthroughMessageClassEncoder;

/**
 * @api
 */
final readonly class MessageEncoder
{
    public function __construct(
        private MessageNormalizer $normalizer = new MessageSerializer(),
        private MessageClassEncoder $classEncoder = new PassthroughMessageClassEncoder(),
        private DataEncoder $dataEncoder = new JsonDataEncoder(),
    ) {}

    public function encodeMessage(Message $message): EncodedMessage
    {
        return new EncodedMessage(
            $this->classEncoder->encodeMessageClass($message::class),
            ...$this->dataEncoder->encodeData($this->normalizer->normalizeMessage($message)),
        );
    }
}
