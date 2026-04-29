<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

final class CannotReply extends \RuntimeException implements Unrecoverable {}
