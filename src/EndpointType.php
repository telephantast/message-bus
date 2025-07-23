<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

enum EndpointType: string
{
    case Consumer = 'consumer';
    case Subscription = 'subscription';
    case Service = 'service';
}
