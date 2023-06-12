<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class UnsupportedReflectionTypeException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Type hints are mandatory and must not be union or intersection types');
    }
}
