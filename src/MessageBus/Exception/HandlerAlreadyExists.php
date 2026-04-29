<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

final class HandlerAlreadyExists extends \LogicException implements Unrecoverable {}
