<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedColumns\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;

#[Entity(factory: PostFactory::class)]
class Post
{
    use WithTimestamps;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $title;

    #[Column]
    public string $content;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;
}
