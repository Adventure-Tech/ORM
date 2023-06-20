<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class EnumSerializationException extends RuntimeException
{
    public function __construct(
        string $message = 'Only native Enum can be serialized.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
