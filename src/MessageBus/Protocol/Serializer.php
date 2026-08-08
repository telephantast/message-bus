<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

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
