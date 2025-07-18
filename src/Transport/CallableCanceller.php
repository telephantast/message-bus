<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

final class CallableCanceller implements Canceller
{
    /**
     * @var ?callable(): void
     */
    private mixed $stopper;

    /**
     * @param callable(): void $stopper
     */
    public function __construct(callable $stopper)
    {
        $this->stopper = $stopper;
    }

    public function cancel(): void
    {
        if ($this->stopper !== null) {
            ($this->stopper)();
            $this->stopper = null;
        }
    }
}
