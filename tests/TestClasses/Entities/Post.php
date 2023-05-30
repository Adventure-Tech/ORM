<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumnAnnotation;
use AdventureTech\ORM\Mapping\Columns\StringColumnAnnotation;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedDatetimes\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;

#[Entity]
class Post
{
    use WithTimestamps;

    #[Id]
    #[IntColumnAnnotation]
    public int $id;

    #[StringColumnAnnotation]
    public string $title;

    #[StringColumnAnnotation]
    public string $content;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;
}
