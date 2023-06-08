<?php

namespace AdventureTech\ORM\AliasingManagement;

class TableAliasingDTO
{
    /**
     * @var array<string,TableAliasingDTO>
     */
    public array $children = [];
    /**
     * @var array<string,string>
     */
    public readonly array $columns;

    /**
     * @param  string  $alias
     * @param  array<int|string,string>  $columns
     */
    public function __construct(public readonly string $alias, array $columns)
    {
        $array = [];
        foreach ($columns as $column) {
            $array[$column] = $column;
        }
        $this->columns = $array;
    }
    public function addChild(string $key, TableAliasingDTO $child): void
    {
        $this->children[$key] = $child;
    }
}
