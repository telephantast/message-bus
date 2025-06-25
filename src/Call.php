<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template-covariant TResult
 * @extends Message<TResult>
 */
interface Call extends Message {}
