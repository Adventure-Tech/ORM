<?php

namespace AdventureTech\ORM\Repository\Filters;

readonly class FilterWhere
{
    /**
     * @param  string  $column
     */
    public function __construct(private string $column)
    {
    }

    public function is(Operator $operator): FilterOperator
    {
        return new FilterOperator($this->column, $operator);
    }

    public function equals(): FilterOperator
    {
        return $this->is(Operator::EQUAL);
    }

    public function like(): FilterOperator
    {
        return $this->is(Operator::LIKE);
    }
}
