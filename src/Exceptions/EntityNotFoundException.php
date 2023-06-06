<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class EntityNotFoundException extends RuntimeException
{
    public function __construct(string $class = null, string|int $id = null, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Entity not found on the DB';
        if (!is_null($class)) {
            $message .= ' [class: "' . $class . '"';
            if (!is_null($id)) {
                $message .=  '", id: "' . $id;
            }
            $message .= '"]';
        }
        parent::__construct($message, $code, $previous);
    }
}
