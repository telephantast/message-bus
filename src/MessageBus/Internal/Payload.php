<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Event;
use Thesis\MessageBus\Metadata;
use Thesis\MessageBus\OutgoingEnvelope;
use Thesis\MessageBus\Reply;

/**
 * @internal
 */
final readonly class Payload
{
    public static function check(object $payload): void
    {
        \assert(self::isValid($payload), $payload::class . ' cannot be used as a payload');
    }

    private static function isValid(object $payload): bool
    {
        return !$payload instanceof Command
            && !$payload instanceof Event
            && !$payload instanceof Reply
            && !$payload instanceof Metadata
            && !$payload instanceof Envelope
            && !$payload instanceof OutgoingEnvelope;
    }

    private function __construct() {}
}
