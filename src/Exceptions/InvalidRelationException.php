<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class InvalidRelationException extends LogicException
{
    public function __construct(string $relation = null, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Invalid relation used in with clause';
        if (!is_null($relation)) {
            $message .= ' [tried to load relation "' . $relation . '"]';
        }
        parent::__construct($message, $code, $previous);
    }
}
