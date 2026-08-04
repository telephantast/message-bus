<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
final class InvalidOutboundMessage extends \LogicException implements MessageBusException {}
