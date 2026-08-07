<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
final class InvalidIntent extends \LogicException implements MessageBusException {}
