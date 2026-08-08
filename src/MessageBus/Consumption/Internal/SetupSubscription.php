<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Thesis\MessageBus\Metadata\Internal\MessageMetadataFactory;
use Thesis\MessageBus\Metadata\MessageKind;
use Thesis\MessageBus\Transport\SubscriptionConfigurator;

/**
 * @internal
 */
final readonly class SetupSubscription
{
    /**
     * @param non-empty-string $endpoint
     * @param list<class-string> $messageClasses
     */
    public function __construct(
        private string $endpoint,
        private MessageMetadataFactory $messageMetadataFactory,
        private SubscriptionConfigurator $subscriptionConfigurator,
        private array $messageClasses,
    ) {}

    public function __invoke(): void
    {
        $eventTypes = [];

        foreach ($this->messageClasses as $messageClass) {
            $metadata = $this->messageMetadataFactory->forClass($messageClass);

            if ($metadata->kind === MessageKind::Event) {
                $eventTypes[] = $metadata->type;
            }
        }

        $this->subscriptionConfigurator->subscribe(
            queue: $this->endpoint,
            messageTypes: array_values(array_unique($eventTypes)),
        );
    }
}
