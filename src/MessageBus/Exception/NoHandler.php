<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

final class NoHandler extends \RuntimeException implements Unrecoverable {}
