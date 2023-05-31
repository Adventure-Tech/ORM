<?php

namespace AdventureTech\ORM\ColumnAliasing;

class ColumnExpression
{
    public array $children = [];
    public readonly array $columns;

    public function __construct(public readonly string $alias, array $columns)
    {
        $arr = [];
        foreach ($columns as $column) {
            $arr[$column] = $column;
        }
        $this->columns = $arr;
    }
    public function addChild(string $key, ColumnExpression $child): void
    {
        $this->children[$key] = $child;
    }
}
