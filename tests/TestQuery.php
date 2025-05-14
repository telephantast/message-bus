<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @implements Message<string>
 */
final readonly class TestQuery implements Message {}
