<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Serialization;

/**
 * @api
 */
interface Serializer
{
    /**
     * @throws MessageSerializationFailed
     */
    public function serialize(object $message): SerializedMessage;
}
