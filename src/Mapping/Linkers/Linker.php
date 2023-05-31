<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
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
     * @param  LocalAliasingManager  $origin
     * @param  LocalAliasingManager  $target
     * @param  array<int,Filter>  $filters
     * @return void
     */
    //public function join(Builder $query, string $from, string $to, array $filters): void;
    public function join(Builder $query, LocalAliasingManager $origin, LocalAliasingManager $target, array $filters): void;

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
