<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(RandomMessageIdGenerator::class)]
final class RandomMessageIdGeneratorTest extends TestCase
{
    /**
     * @param positive-int $bytes
     */
    #[TestWith([2])]
    #[TestWith([4])]
    #[TestWith([8])]
    #[TestWith([16])]
    public function testItGeneratesExpectedIdPattern(int $bytes): void
    {
        $generator = new RandomMessageIdGenerator($bytes);

        $id = $generator->generateMessageId();

        self::assertSame($bytes * 2, \strlen($id));
        self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $id);
    }

    public function testLengthIs16(): void
    {
        $generator = new RandomMessageIdGenerator();

        $id = $generator->generateMessageId();

        self::assertSame(32, \strlen($id));
    }
}
