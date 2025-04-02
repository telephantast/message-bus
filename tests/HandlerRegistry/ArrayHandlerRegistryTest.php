<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use PHPUnit\Framework\Attributes\CoversClass;
use Thesis\MessageBus\HandlerRegistry;

#[CoversClass(ArrayHandlerRegistry::class)]
final class ArrayHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $messageClassToHandler): HandlerRegistry
    {
        return new ArrayHandlerRegistry($messageClassToHandler);
    }
}
