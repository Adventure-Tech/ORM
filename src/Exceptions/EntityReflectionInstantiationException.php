<?php

namespace AdventureTech\ORM\Exceptions;

use LogicException;

class EntityReflectionInstantiationException extends LogicException
{
    public function __construct(string $class = "")
    {
        $message = 'EntityReflection class can only be instantiated for a valid entity';
        if (!is_null($class)) {
            $message .= ' [attempted instantiation for "' . $class . '"]';
        }
        parent::__construct($message);
    }
}
