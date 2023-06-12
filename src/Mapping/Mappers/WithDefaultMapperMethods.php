<?php

namespace AdventureTech\ORM\Mapping\Mappers;

trait WithDefaultMapperMethods
{
    /**
     * @param  string  $name
     */
    public function __construct(private readonly string $name)
    {
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }
}
