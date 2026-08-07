<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Thesis\Headers;

/**
 * @internal
 */
interface ImmediateMessageHandler
{
    public function handleImmediately(object $message, Headers $headers): void;
}
