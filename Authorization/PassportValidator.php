<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

/**
 * @api
 * @template TPassport of object
 */
interface PassportValidator
{
    /**
     * @psalm-assert-if-true TPassport $passport
     */
    public function isValid(object $passport): bool;
}
