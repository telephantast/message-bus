<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

use Telephantast\MessageBus\InheritableContextAttribute;
use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final class Authorized implements Stamp, InheritableContextAttribute {}
