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
use JetBrains\PhpStorm\Deprecated;

#[Entity]
class User
{
    use WithTimestamps;
    use WithSoftDeletes;

    #[Id]
    #[Column(name: 'id')]
    private int $identifier;

    #[Column]
    public string $name;

    #[Column]
    #[Deprecated]
    public ?string $favouriteColor;

    /**
     * @var Collection<int,Post>
     */
    #[HasMany(targetEntity: Post::class, foreignKey: 'author')]
    public Collection $posts;

    /**
     * @var Collection<int,Comment>
     */
    #[HasMany(targetEntity: Comment::class, foreignKey: 'author')]
    public Collection $comments;

    #[HasOne]
    public ?PersonalDetails $personalDetails;

    #[BelongsToMany(
        targetEntity: User::class,
        pivotTable: 'friends',
        originForeignKey: 'a_id',
        targetForeignKey: 'b_id'
    )]
    public Collection $friends;

    public function getIdentifier(): ?int
    {
        return $this->identifier ?? null;
    }

    public function setIdentifier(int $id): void
    {
        $this->identifier = $id;
    }
}
