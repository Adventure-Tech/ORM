<?php

namespace AdventureTech\ORM\Exceptions;

use RuntimeException;
use Throwable;

class CannotRestoreHardDeletedRecordException extends RuntimeException // TODO
{
    public function __construct(string $message = 'Cannot restore entity without soft-deletes', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
