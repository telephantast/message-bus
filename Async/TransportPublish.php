<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Amp\Future;
use Telephantast\Message\Message;
use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
interface TransportPublish
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param Envelope<TResult, TMessage> $envelope
     * @return Future<void>
     */
    public function publish(Envelope $envelope): Future;
}
