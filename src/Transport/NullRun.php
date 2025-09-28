<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

enum NullRun implements Run
{
    case Instance;

    public function stop(): void {}
}
