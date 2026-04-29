<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

final class NoEndpoint extends \RuntimeException implements Unrecoverable {}
