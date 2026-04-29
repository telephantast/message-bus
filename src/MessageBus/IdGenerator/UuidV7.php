<?php

declare(strict_types=1);

namespace Thesis\MessageBus\IdGenerator;

use Thesis\MessageBus\IdGenerator;
use Thesis\MessageBus\IdGenerator\Internal\RandomBuffer;

/**
 * @api
 *
 * @see https://www.rfc-editor.org/rfc/rfc9562#name-uuid-version-7
 */
final class UuidV7 implements IdGenerator
{
    private readonly RandomBuffer $randomBuffer;

    /**
     * @var non-negative-int
     */
    private int $lastMs = 0;

    /**
     * @var non-negative-int
     */
    private int $seq = 0;

    public function __construct()
    {
        $this->randomBuffer = new RandomBuffer();
    }

    public function generateId(): string
    {
        $ms = (int) (microtime(true) * 1_000);
        $rand = $this->randomBuffer->nextInt64();

        if ($ms > $this->lastMs) {
            $this->lastMs = $ms;
            $this->seq = ($rand >> 52) & 0xF_FF;
        } else {
            ++$this->seq;

            if ($this->seq > 0xF_FF) {
                ++$this->lastMs;
                $this->seq = 0;
            }
        }

        return \sprintf(
            '%08x-%04x-%04x-%04x-%012x',
            ($this->lastMs >> 16) & 0xFF_FF_FF_FF,
            $this->lastMs & 0xFF_FF,
            0x70_00 | $this->seq,
            0x80_00 | (($rand >> 48) & 0x3F_FF),
            $rand & 0xFF_FF_FF_FF_FF_FF,
        );
    }
}
