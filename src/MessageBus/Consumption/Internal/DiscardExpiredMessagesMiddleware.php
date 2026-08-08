<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;
use const Thesis\MessageBus\Protocol\EXPIRES_AT;
use const Thesis\MessageBus\Protocol\MESSAGE_ID;
use const Thesis\MessageBus\Protocol\MESSAGE_TYPE;

/**
 * @internal
 */
final readonly class DiscardExpiredMessagesMiddleware implements ConsumerMiddleware
{
    public function __construct(
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {}

    public function process(string $endpoint, InboundEnvelope $envelope, ConsumerHandler $handler): Disposition
    {
        $expiresAt = $envelope->headers->find(EXPIRES_AT);

        if ($expiresAt === null || $expiresAt > $this->clock->now()) {
            return $handler->handle($envelope);
        }

        $this->logger->debug('Message expired; discarding.', [
            'endpoint' => $endpoint,
            'message_id' => $envelope->headers->find(MESSAGE_ID),
            'message_type' => $envelope->headers->find(MESSAGE_TYPE),
            'expired_at' => $expiresAt,
        ]);

        return Disposition::Ack;
    }
}
