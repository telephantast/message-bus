<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

use Thesis\MessageBus\Envelope;
use Thesis\Pgmq\Message;
use Thesis\Pgmq\SendMessage;

/**
 * @api
 */
interface Serializer
{
    public function serialize(Envelope $envelope): SendMessage;

    public function deserialize(Message $message): Envelope;
}
