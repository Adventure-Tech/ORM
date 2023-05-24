<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumn;
use AdventureTech\ORM\Mapping\Columns\StringColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Relations\BelongsToMany;
use AdventureTech\ORM\Mapping\Relations\HasMany;
use AdventureTech\ORM\Mapping\Relations\HasOne;
use AdventureTech\ORM\Mapping\WithSoftDeletes;
use AdventureTech\ORM\Mapping\WithTimestamps;
use Illuminate\Support\Collection;

#[Entity]
class User
{
    use WithTimestamps;
    use WithSoftDeletes;

    #[Id]
    #[IntColumn]
    public int $id;

    #[StringColumn]
    public string $name;

    #[HasMany(targetEntity: Post::class)]
    public Collection $posts;

    #[HasOne]
    public PersonalDetails $personalDetails;

    #[BelongsToMany(
        targetEntity: User::class,
        pivotTable: 'friends',
        key1: 'a_id',
        key2: 'b_id'
    )]
    public Collection $friends;
}
