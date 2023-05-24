<?php

namespace AdventureTech\ORM\Exceptions;

class NotInitializedException extends \LogicException
{
    public function __construct(string $class = "")
    {
        $message = 'Must initialize before using: ' . $class;
        parent::__construct($message);
    }
}
