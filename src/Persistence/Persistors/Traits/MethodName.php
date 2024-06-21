<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

enum MethodName: string
{
    case INSERT = 'insert';
    case DELETE = 'delete';
    case UPDATE = 'update';
    case RESTORE = 'restore';

    case FORCE_DELETE = 'forceDelete';
    case ATTACH = 'attach';
    case DETACH = 'detach';
}
