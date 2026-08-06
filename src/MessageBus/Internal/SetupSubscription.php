<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

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
        private array $messageClasses,
        private MessageMetadataRegistry $messageMetadataRegistry,
        private SubscriptionConfigurator $subscriptionConfigurator,
    ) {}

    public function __invoke(): void
    {
        $eventTypes = [];

        foreach ($this->messageClasses as $messageClass) {
            $metadata = $this->messageMetadataRegistry->forClass($messageClass);

            if ($metadata->isEvent) {
                $eventTypes[] = $metadata->type;
            }
        }

        $eventTypes = array_values(array_unique($eventTypes));

        $this->subscriptionConfigurator->subscribe($this->endpoint, $eventTypes);
    }
}
