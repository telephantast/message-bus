<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence\Outbox;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class OutboxDoesNotExist extends MessageBusException {}
