<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Dispatcher;

use Thesis\MessageBus\Envelope;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Transactional
{
    /**
     * @param Tx $transaction
     * @param non-empty-list<Envelope> $messages
     */
    public function transactionalDispatch(object $transaction, array $messages): void;
}
