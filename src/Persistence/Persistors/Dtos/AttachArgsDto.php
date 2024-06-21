<?php

namespace AdventureTech\ORM\Persistence\Persistors\Dtos;

use Illuminate\Support\Collection;

final readonly class AttachArgsDto
{
    /**
     * @var Collection<int|string,object>
     */
    public Collection $linkedEntities;

    /**
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     */
    public function __construct(Collection|array $linkedEntities, public string $relation)
    {
        $this->linkedEntities = Collection::wrap($linkedEntities);
    }
}
