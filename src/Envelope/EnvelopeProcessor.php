<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface EnvelopeProcessor
{
    /**
     * @template TMessage of object
     * @param Envelope<TMessage> $envelope
     * @param ?Envelope<*> $cause
     * @return Envelope<TMessage>
     */
    public function process(Endpoint $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope;
}
