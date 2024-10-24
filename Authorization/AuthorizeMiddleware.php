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
final class AuthorizeMiddleware implements Middleware
{
    /**
     * @param PassportValidator<TPassport> $passportValidator
     * @param AuthorizerRegistry<TPassport> $authorizerRegistry
     */
    public function __construct(
        public readonly PassportValidator $passportValidator,
        public readonly AuthorizerRegistry $authorizerRegistry,
    ) {}

    /**
     * @throws MessageAuthorizationFailed
     */
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(Authorized::class) || $messageContext->hasAttribute(Authorized::class)) {
            $authorized = new Authorized();
            $messageContext->setStamp($authorized);
            $messageContext->setAttribute($authorized);

            return $pipeline->continue();
        }

        $passport = $this->resolvePassport($messageContext);
        $messageClass = $messageContext->getMessageClass();
        $authorizer = $this->authorizerRegistry->get($messageClass);

        if ($authorizer === null) {
            throw new MessageAuthorizationFailed(\sprintf('No authorizer for message %s.', $messageClass));
        }

        $result = $authorizer($passport, $messageContext->getMessage());

        if ($result === false) {
            throw new MessageAuthorizationFailed(\sprintf('Failed to authorize message %s.', $messageClass));
        }

        if ($result !== true) {
            if (!$result instanceof $messageClass) {
                throw new \UnexpectedValueException(\sprintf('Authorizer must return an instance of %s', $messageClass));
            }

            $messageContext->setMessage($result);
        }

        $authorized = new Authorized();
        $messageContext->setStamp($authorized);
        $messageContext->setAttribute($authorized);

        return $pipeline->continue();
    }

    /**
     * @return ?TPassport
     * @throws MessageAuthorizationFailed
     */
    private function resolvePassport(MessageContext $messageContext): ?object
    {
        $passport = $messageContext->getStamp(Authentication::class)?->passport;

        if ($passport === null) {
            return null;
        }

        if ($this->passportValidator->isValid($passport)) {
            return $passport;
        }

        throw new MessageAuthorizationFailed('Invalid authentication passport in message.');
    }
}
