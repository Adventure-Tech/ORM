<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedColumns\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;
use AdventureTech\ORM\Tests\TestClasses\PostRepository;
use Carbon\CarbonImmutable;

#[Entity(repository: PostRepository::class, factory: PostFactory::class)]
class Post
{
    use WithTimestamps;

//    use WithSoftDeletes;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $title;

    #[Column]
    public string $content;

    #[DatetimeTZColumn(tzName: 'published_tz')]
    public ?CarbonImmutable $publishedAt = null;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;
}
