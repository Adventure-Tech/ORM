<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedColumns\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsToMany;
use AdventureTech\ORM\Mapping\Relations\HasMany;
use AdventureTech\ORM\Mapping\Relations\HasOne;
use AdventureTech\ORM\Mapping\SoftDeletes\WithSoftDeletes;
use Illuminate\Support\Collection;

#[Entity]
class User
{
    use WithTimestamps;
    use WithSoftDeletes;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $name;

    #[Column]
    public ?string $favouriteColor = null;

    #[HasMany(targetEntity: Post::class, foreignKey: 'author')]
    public Collection $posts;

    #[HasOne]
    public ?PersonalDetails $personalDetails;

    #[BelongsToMany(
        targetEntity: User::class,
        pivotTable: 'friends',
        originForeignKey: 'a_id',
        targetForeignKey: 'b_id'
    )]
    public Collection $friends;
}
