<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Call;

/**
 * @implements Call<string>
 */
final readonly class TestQuery implements Call {}
