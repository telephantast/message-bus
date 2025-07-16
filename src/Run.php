<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

enum Run: string
{
    case Commands = 'commands';
    case Events = 'events';
    case Calls = 'calls';
}
