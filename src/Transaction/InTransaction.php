<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transaction;

use Thesis\MessageBus\InheritableContextAttribute;

/**
 * @api
 */
final readonly class InTransaction implements InheritableContextAttribute {}
