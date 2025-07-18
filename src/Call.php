<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * An interface for calls.
 * A call is routed to a single handler.
 * `TResult` specifies the expected handler result type.
 *
 * @api
 * @template-covariant TResult
 */
interface Call {}
