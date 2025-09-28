<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
enum Ack
{
    case Value;
}

/**
 * @api
 */
const Ack = Ack::Value;
