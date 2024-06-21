<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class IdSetForInsertException extends RuntimeException // TODO
{
    public function __construct(
        string $message = 'Must not set ID column for insert',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
