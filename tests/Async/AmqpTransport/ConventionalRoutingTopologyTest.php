<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Message\Message;

#[CoversClass(ConventionalRoutingTopology::class)]
final class ConventionalRoutingTopologyTest extends TestCase
{
    /**
     * @param class-string<Message<mixed>> $class
     */
    #[TestWith(['A', 'A'])]
    #[TestWith(['A\B', 'A.B'])]
    public function test(string $class, string $expectedExchange): void
    {
        $topology = new ConventionalRoutingTopology();

        $exchange = $topology->resolveExchange($class);

        self::assertSame($expectedExchange, $exchange);
    }
}
