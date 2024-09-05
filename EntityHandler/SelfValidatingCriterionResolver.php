<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

/**
 * @api
 */
interface SelfValidatingCriterionResolver extends CriterionResolver
{
    public function checkValidFor(\ReflectionClass $messageClass): void;
}
