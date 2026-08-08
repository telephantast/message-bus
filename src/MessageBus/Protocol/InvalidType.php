<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class InvalidType extends \LogicException implements MessageBusException {}
