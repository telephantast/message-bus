<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

interface Parameter
{
    public static function tryFrom(\ReflectionParameter $parameter): ?static;

    /**
     * @param non-empty-string $endpoint
     * @param Envelope<*> $envelope
     */
    public function resolveArgument(string $endpoint, Envelope $envelope, Context $context): mixed;
}
