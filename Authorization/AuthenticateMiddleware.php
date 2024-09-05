<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 * @template TPassport of object
 */
final class AuthenticateMiddleware implements Middleware
{
    /**
     * @param AuthenticationContext<TPassport> $authenticationContext
     */
    public function __construct(
        public readonly AuthenticationContext $authenticationContext,
    ) {}

    /**
     * @throws MessageAuthorizationFailed
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $passport = $this->authenticationContext->passport();

        if ($passport !== null) {
            $messageContext->setStamp(new Authentication($passport));
        }

        return $pipeline->continue();
    }
}
