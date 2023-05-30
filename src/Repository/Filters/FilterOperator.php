<?php

namespace AdventureTech\ORM\Repository\Filters;

readonly class FilterOperator
{
    /**
     * @param  string  $column
     * @param  Operator  $operator
     */
    public function __construct(private string $column, private Operator $operator)
    {
    }

    public function value(mixed $value): DefaultFilter
    {
        return new DefaultFilter($this->column, $this->operator, $value, false);
    }

    public function column(string $column): DefaultFilter
    {
        return new DefaultFilter($this->column, $this->operator, $column, true);
    }
}
