<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
interface Serializer
{
    /**
     * @throws SerializationFailed
     */
    public function serialize(object $message): SerializedMessage;
}
