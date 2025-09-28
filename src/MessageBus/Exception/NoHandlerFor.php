<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Exception;

/**
 * @api
 */
final class NoHandlerFor extends \RuntimeException implements Unrecoverable
{
    /**
     * @param class-string $payloadClass
     */
    public function __construct(string $payloadClass)
    {
        parent::__construct("No handler for payload class `{$payloadClass}`");
    }
}
