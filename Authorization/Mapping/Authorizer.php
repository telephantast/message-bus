<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final class Authorizer {}
