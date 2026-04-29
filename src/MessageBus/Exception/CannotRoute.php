<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

final class CannotRoute extends \RuntimeException implements Unrecoverable {}
