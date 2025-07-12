<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class OutboxAlreadyExists extends MessageBusException {}
