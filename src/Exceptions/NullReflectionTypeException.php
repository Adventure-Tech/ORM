<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class NullReflectionTypeException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Reflection type returned null');
    }
}
