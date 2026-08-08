<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class InvalidKind extends \LogicException implements MessageBusException {}
