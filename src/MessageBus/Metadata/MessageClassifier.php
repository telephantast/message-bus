<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
interface MessageClassifier
{
    /**
     * @param class-string $messageClass
     * @return MessageKind|null null when the resolver cannot determine the message kind
     */
    public function kindOf(string $messageClass): ?MessageKind;
}
