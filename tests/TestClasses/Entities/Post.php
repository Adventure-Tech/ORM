<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\IntColumn;
use AdventureTech\ORM\Mapping\Columns\StringColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Mapping\WithSoftDeletes;
use AdventureTech\ORM\Mapping\WithTimestamps;

#[Entity]
class Post
{
    use WithTimestamps;

    #[Id]
    #[IntColumn]
    public int $id;

    #[StringColumn]
    public string $title;

    #[StringColumn]
    public string $content;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;
}
