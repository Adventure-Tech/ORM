<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;

#[Entity]
class Comment
{
    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $comment;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;

    #[BelongsTo]
    public Post $post;
}
