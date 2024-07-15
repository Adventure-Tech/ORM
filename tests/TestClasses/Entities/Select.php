<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\SoftDeletes\WithSoftDeletes;

#[Entity(table: 'select')]
class Select
{
    use WithSoftDeletes;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $end;
}
