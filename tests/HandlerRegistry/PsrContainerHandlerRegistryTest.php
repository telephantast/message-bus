<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use PHPUnit\Framework\Attributes\CoversClass;
use Thesis\MessageBus\HandlerRegistry;

#[CoversClass(PsrContainerHandlerRegistry::class)]
final class PsrContainerHandlerRegistryTest extends HandlerRegistryTestCase
{
    protected function createHandlerRegistry(array $messageClassToHandler): HandlerRegistry
    {
        return new PsrContainerHandlerRegistry(
            new ArrayPsrContainer($messageClassToHandler),
        );
    }
}
