<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

interface Run
{
    public function stop(): void;
}
