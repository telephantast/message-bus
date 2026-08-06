<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
enum Disposition
{
    /**
     * The inbound message was handled successfully and must not be delivered again.
     */
    case Ack;

    /**
     * The inbound message was not handled successfully, but the failure was handled.
     * The transport must not deliver the same inbound message again.
     */
    case Nack;

    /**
     * The inbound message was not handled and should be delivered again by the transport.
     */
    case Requeue;
}
