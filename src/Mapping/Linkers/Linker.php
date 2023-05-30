<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\Repository\Filters\Filter;
use Illuminate\Database\Query\Builder;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 */
interface Linker
{
    /**
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @param  array<int,Filter>  $filters
     * @return void
     */
    public function join(Builder $query, string $from, string $to, array $filters): void;

    /**
     * @return class-string<TARGET>
     */
    public function getTargetEntity(): string;

    /**
     * @param  ORIGIN  $currentEntity
     * @param  TARGET|null  $relatedEntity
     * @return void
     */
    public function link(object $currentEntity, ?object $relatedEntity): void;
}
