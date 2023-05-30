<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumnAnnotation;
use AdventureTech\ORM\Mapping\Columns\StringColumnAnnotation;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedDatetimes\WithTimestamps;
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
    #[IntColumnAnnotation]
    public int $id;

    #[StringColumnAnnotation]
    public string $name;

    #[HasMany(targetEntity: Post::class)]
    public Collection $posts;

    #[HasOne]
    public PersonalDetails $personalDetails;

    #[BelongsToMany(
        targetEntity: User::class,
        pivotTable: 'friends',
        originForeignKey: 'a_id',
        targetForeignKey: 'b_id'
    )]
    public Collection $friends;
}
