<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

use function Thesis\exceptionally;

/**
 * @api
 */
final readonly class PhpNativeSerializer implements Serializer, Deserializer
{
    public const string CONTENT_TYPE = 'application/php-serialized';

    /**
     * @var array{allowed_classes?: list<class-string>}
     */
    private array $unserializeOptions;

    /**
     * @param ?list<class-string> $allowedClasses
     */
    public function __construct(?array $allowedClasses = null)
    {
        $this->unserializeOptions = $allowedClasses === null ? [] : ['allowed_classes' => $allowedClasses];
    }

    public function serialize(object $message): SerializedMessage
    {
        try {
            return new SerializedMessage(
                payload: serialize($message),
                contentType: self::CONTENT_TYPE,
            );
        } catch (\Throwable $exception) {
            throw new SerializationFailed(
                message: \sprintf('Failed to serialize "%s": %s', $message::class, $exception->getMessage()),
                previous: $exception,
            );
        }
    }

    public function deserialize(SerializedMessage $serializedMessage, string $messageClass): object
    {
        try {
            $message = exceptionally(fn() => unserialize($serializedMessage->payload, $this->unserializeOptions));
        } catch (\Throwable $exception) {
            throw new DeserializationFailed(
                message: 'Failed to unserialize message payload',
                previous: $exception,
            );
        }

        if (!$message instanceof $messageClass) {
            throw new DeserializationFailed(\sprintf(
                'Serialized payload did not produce an instance of "%s".',
                $messageClass,
            ));
        }

        return $message;
    }
}
