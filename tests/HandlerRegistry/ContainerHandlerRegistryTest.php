<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use PHPUnit\Framework\Attributes\CoversClass;
use Thesis\MessageBus\HandlerRegistry;

#[CoversClass(ContainerHandlerRegistry::class)]
final class ContainerHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $messageClassToHandler): HandlerRegistry
    {
        return new ContainerHandlerRegistry(
            new ArrayContainer($messageClassToHandler),
        );
    }
}
