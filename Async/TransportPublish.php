<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

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
     * @param non-empty-list<Envelope<TResult, TMessage>> $envelopes
     */
    public function publish(array $envelopes): void;
}
