<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

final class CallableRun implements Run
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

    public function stop(): void
    {
        if ($this->stopper !== null) {
            ($this->stopper)();
            $this->stopper = null;
        }
    }
}
