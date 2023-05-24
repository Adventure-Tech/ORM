<?php

namespace AdventureTech\ORM\Mapping\Relations;

use Illuminate\Database\Query\Builder;

interface Relation
{
    public function resolveDefault(
        string $propertyName,
        string $propertyType,
        string $className
    ): void;
    public function join(Builder $query, string $from, string $to): void;
    public function getTargetEntity(): string;

    public function link(object $currentEntity, ?object $relatedEntity): void;
}
