<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
enum Kind: string implements \JsonSerializable
{
    case Command = 'command';
    case Event = 'event';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
