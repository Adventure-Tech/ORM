<?php

namespace AdventureTech\ORM\Tests\TestClasses\Entities;

use AdventureTech\ORM\Mapping\Columns\Column;
use AdventureTech\ORM\Mapping\Columns\DatetimeTZColumn;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Mapping\Id;
use AdventureTech\ORM\Mapping\ManagedColumns\WithTimestamps;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use AdventureTech\ORM\Mapping\Relations\HasMany;
use AdventureTech\ORM\Mapping\SoftDeletes\WithSoftDeletes;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;
use AdventureTech\ORM\Tests\TestClasses\IntEnum;
use AdventureTech\ORM\Tests\TestClasses\Repositories\PostRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

#[Entity(repository: PostRepository::class, factory: PostFactory::class)]
class Post
{
    use WithTimestamps;
    use WithSoftDeletes;

    #[Id]
    #[Column]
    public int $id;

    #[Column]
    public string $title;

    #[Column]
    public string $content;

    #[Column]
    public IntEnum $number;

    #[DatetimeTZColumn(tzName: 'published_tz')]
    public ?CarbonImmutable $publishedAt;

    #[BelongsTo(foreignKey: 'author')]
    public User $author;

    #[BelongsTo(foreignKey: 'editor')]
    public ?User $editor;

    /**
     * @var Collection<int,Comment>
     */
    #[HasMany(targetEntity: Comment::class)]
    public Collection $comments;
}
