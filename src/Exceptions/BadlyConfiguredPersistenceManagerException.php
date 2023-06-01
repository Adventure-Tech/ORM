<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class BadlyConfiguredPersistenceManagerException extends LogicException
{
    public function __construct(
        string $message = 'Need to set $entity when extending',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
