<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class MessageClassifiers implements MessageClassifier
{
    /**
     * @param list<MessageClassifier> $classifiers
     */
    public function __construct(
        private array $classifiers,
    ) {}

    public function kindOf(string $messageClass): ?MessageKind
    {
        foreach ($this->classifiers as $resolver) {
            $kind = $resolver->kindOf($messageClass);

            if ($kind !== null) {
                return $kind;
            }
        }

        return null;
    }
}
