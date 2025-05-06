<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Dispatching;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface OutgoingEnvelopeProcessor
{
    public function process(Envelope $envelope, DispatchingContext $context): Envelope;
}
