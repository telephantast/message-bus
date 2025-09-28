<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
enum Reject
{
    case Value;
}

/**
 * @api
 */
const Reject = Reject::Value;
