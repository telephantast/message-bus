<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @return non-empty-string
 */
function messageId(Envelope|MessageContext $envelopeOrMessageContext): string
{
    /** @phpstan-ignore nullsafe.neverNull */
    return $envelopeOrMessageContext->stamp(MessageId::class)?->messageId
        ?? throw new NoMessageId($envelopeOrMessageContext->messageClass());
}
