<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @implements Message<null>
 */
final class TestMessage implements Message {}
