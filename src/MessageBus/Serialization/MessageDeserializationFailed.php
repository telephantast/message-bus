<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Serialization;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class MessageDeserializationFailed extends \RuntimeException implements MessageBusException {}
