<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Routing;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class CannotRouteCommand extends \LogicException implements MessageBusException
{
    /**
     * @param class-string $commandClass
     */
    public function __construct(string $commandClass)
    {
        parent::__construct(\sprintf(
            'Cannot route command %s: specify destination endpoint explicitly when sending or add a #[Destination] attribute or configure a custom CommandRouter',
            $commandClass,
        ));
    }
}
