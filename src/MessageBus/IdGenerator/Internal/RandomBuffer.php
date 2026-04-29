<?php

declare(strict_types=1);

namespace Thesis\MessageBus\IdGenerator\Internal;

/**
 * @internal
 */
final class RandomBuffer
{
    private const int SIZE = 512;

    private string $buffer = '';

    /**
     * @var non-negative-int
     */
    private int $pos = self::SIZE;

    public function nextInt64(): int
    {
        if ($this->pos >= self::SIZE) {
            $this->buffer = random_bytes(self::SIZE);
            $this->pos = 0;
        }

        /**
         * @var int $rand
         * @phpstan-ignore offsetAccess.nonOffsetAccessible
         */
        $rand = unpack('J', $this->buffer, $this->pos)[1];
        $this->pos += 8;

        return $rand;
    }
}
