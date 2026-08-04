<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Serialization;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class MessageSerializationFailed extends \RuntimeException implements MessageBusException {}
