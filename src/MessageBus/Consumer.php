<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;
use Amp\NullCancellation;

/**
 * @api
 */
interface Consumer
{
    public function stop(): void;

    public function awaitCompletion(Cancellation $cancellation = new NullCancellation()): void;
}
