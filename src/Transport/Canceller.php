<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

interface Canceller
{
    public function cancel(): void;
}
