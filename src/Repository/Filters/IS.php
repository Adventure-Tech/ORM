<?php

namespace AdventureTech\ORM\Repository\Filters;

enum IS: string
{
    case LIKE = 'like';
    case EQUAL = '=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL_TO = '<=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL_TO = '>=';
    case NOT_EQUAL = '!=';
}
