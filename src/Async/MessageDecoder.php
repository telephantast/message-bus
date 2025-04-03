<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Async\MessageEncoder\DataDecoder;
use Thesis\MessageBus\Async\MessageEncoder\JsonDataEncoder;
use Thesis\MessageBus\Async\MessageEncoder\MessageClassDecoder;
use Thesis\MessageBus\Async\MessageEncoder\MessageDenormalizer;
use Thesis\MessageBus\Async\MessageEncoder\MessageSerializer;
use Thesis\MessageBus\Async\MessageEncoder\PassthroughMessageClassEncoder;

/**
 * @api
 */
final readonly class MessageDecoder
{
    public function __construct(
        private MessageClassDecoder $classDecoder = new PassthroughMessageClassEncoder(),
        private DataDecoder $dataDecoder = new JsonDataEncoder(),
        private MessageDenormalizer $denormalizer = new MessageSerializer(),
    ) {}

    public function decodeMessage(EncodedMessage $message): Message
    {
        return $this->denormalizer->denormalizeMessage(
            $this->classDecoder->decodeMessageClass($message->type),
            $this->dataDecoder->decodeData($message->contentType, $message->body),
        );
    }
}
