<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class FromContext {}
