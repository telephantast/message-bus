<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class FailedToPublishMessages extends MessageBusException {}
