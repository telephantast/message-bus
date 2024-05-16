<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\HandlerRegistry;

use Telephantast\Message\Event;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\HandlerRegistry;

/**
 * @api
 */
final readonly class EnsureNonEventMessageHasHandler implements HandlerRegistry
{
    public function __construct(
        private HandlerRegistry $handlerRegistry,
    ) {}

    public function get(string $messageClass): ?Handler
    {
        $handler = $this->handlerRegistry->get($messageClass);

        if ($handler !== null) {
            /** @phpstan-ignore return.type */
            return $handler;
        }

        if (is_subclass_of($messageClass, Event::class)) {
            return null;
        }

        throw new \RuntimeException(sprintf('No handler for non-event message %s.', $messageClass));
    }
}
