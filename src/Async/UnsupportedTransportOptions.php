<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

/**
 * @api
 */
final class UnsupportedTransportOptions extends \InvalidArgumentException
{
    /**
     * @param class-string<TransportPublish> $transport
     * @param class-string<TransportOptions> $received
     * @param list<class-string<TransportOptions>> $supported
     */
    public function __construct(string $transport, string $received, array $supported = [])
    {
        if ($supported === []) {
            parent::__construct(\sprintf(
                '`%s` does not support transport options, received: `%s`',
                $transport,
                $received,
            ));
        } else {
            parent::__construct(\sprintf(
                '`%s` supports transport options of type `%s`, received: `%s`',
                $transport,
                implode('|', $supported),
                $received,
            ));
        }
    }
}
