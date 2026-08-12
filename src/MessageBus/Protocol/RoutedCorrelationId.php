<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

/**
 * @api
 */
final class RoutedCorrelationId
{
    /**
     * @param non-empty-string $handlerQualifier
     * @param non-empty-string $id
     */
    public function __construct(
        public string $handlerQualifier,
        public string $id,
    ) {}
}
