<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface EnvelopeProcessor
{
    /**
     * @template TMessage of object
     * @param Envelope<TMessage> $envelope
     * @return Envelope<TMessage>
     */
    public function process(Envelope $envelope, ?Envelope $cause = null): Envelope;
}
