<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;
use Throwable;

class EntityInstantiationException extends LogicException
{
    public function __construct(string $class, Throwable $previous)
    {
        $message = 'EntityReflection failed to instantiate entity for "' . $class . '"';
        parent::__construct(message: $message, previous:  $previous);
    }
}
