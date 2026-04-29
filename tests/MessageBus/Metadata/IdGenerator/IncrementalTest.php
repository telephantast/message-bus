<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\IdGenerator;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Increment::class)]
final readonly class IncrementalTest
{
    public function startsAtOne(): void
    {
        Assert::same('1', new Increment()->generateId());
    }

    public function customStartValue(): void
    {
        Assert::same('42', new Increment(42)->generateId());
    }

    public function sequential(): void
    {
        $generator = new Increment();

        Assert::same(['1', '2', '3'], [
            $generator->generateId(),
            $generator->generateId(),
            $generator->generateId(),
        ]);
    }
}
