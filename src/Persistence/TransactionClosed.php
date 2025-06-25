<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class TransactionClosed extends MessageBusException {}
