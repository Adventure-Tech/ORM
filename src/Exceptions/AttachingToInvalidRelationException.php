<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class AttachingToInvalidRelationException extends LogicException
{
    public function __construct(string $message = 'Can only attach pure many-to-many relations', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
