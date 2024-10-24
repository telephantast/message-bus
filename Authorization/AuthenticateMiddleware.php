<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;

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
