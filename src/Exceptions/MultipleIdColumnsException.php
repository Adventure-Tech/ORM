<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class MultipleIdColumnsException extends LogicException
{
    public function __construct(string $message = 'Cannot have multiple ID columns', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
