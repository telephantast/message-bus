<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Encoding;

/**
 * @api
 */
final readonly class EncodedData
{
    public function __construct(
        public string $data,
        public ?string $type = null,
        public ?string $encoding = null,
    ) {}

    /**
     * @return non-negative-int
     */
    public function length(): int
    {
        return \strlen($this->data);
    }
}
