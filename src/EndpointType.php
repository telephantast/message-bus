<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

enum EndpointType: string
{
    case Queue = 'queue';
    case Subscription = 'subscription';
    case Service = 'endpoint';
}
